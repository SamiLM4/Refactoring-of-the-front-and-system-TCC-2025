<?php

require_once __DIR__ . "/BaseController.php";
require_once __DIR__ . "/../modelo/IAModel.php";
require_once __DIR__ . "/../modelo/PacienteModel.php";

class IAController extends BaseController
{
    private $model;
    private $pacienteModel;

    // ==== CONFIGURAÇÕES (via .env — ver getenv abaixo) ====
    private const MAX_IMAGENS = 6;      // limite de imagens por requisição
    private const MAX_LADO_PX = 768;    // redimensiona imagens grandes antes de enviar (reduz custo de tokens)

    public function __construct()
    {
        $this->model = new IAModel();
        $this->pacienteModel = new PacienteModel();
    }

    public function analyze()
    {
        $usuario = $GLOBALS['usuario'];

        $paciente_id = $_POST['paciente_id'] ?? null;
        if (!$paciente_id || !isset($_FILES['imagens']) || empty($_FILES['imagens']['tmp_name'])) {
            $this->errorResponse("Paciente e ao menos uma imagem de MRI são obrigatórios");
        }

        $paciente = $this->pacienteModel->getById($paciente_id, $usuario['instituicao_id']);
        if (!$paciente) {
            $this->errorResponse("Paciente não encontrado", 404);
        }

        $imagensTmp = $_FILES['imagens']['tmp_name'];
        $imagensNomes = $_FILES['imagens']['name'];
        if (!is_array($imagensTmp)) {
            $imagensTmp = [$imagensTmp];
            $imagensNomes = [$imagensNomes];
        }

        if (count($imagensTmp) > self::MAX_IMAGENS) {
            $this->errorResponse("Envie no máximo " . self::MAX_IMAGENS . " imagens por requisição.");
        }

        $apiKey  = getenv('OPENAI_API_KEY');
        $devMode = filter_var(getenv('IA_DEV_MODE'), FILTER_VALIDATE_BOOLEAN);

        if (!$devMode && !$apiKey) {
            $this->errorResponse("OPENAI_API_KEY não configurada no ambiente.", 500);
        }

        // Processa cada imagem: redimensiona se necessário e converte para base64
        // As imagens são armazenadas diretamente no banco como base64 (sem salvar em disco)
        $imagensBase64 = []; // array de {mime, data} para salvar no banco
        $content = [["type" => "text", "text" => ""]];

        foreach ($imagensTmp as $i => $tmp) {
            if (!$tmp || !file_exists($tmp)) {
                continue;
            }

            $mimeOriginal = mime_content_type($tmp);
            if (!in_array($mimeOriginal, ['image/jpeg', 'image/png', 'image/webp'])) {
                continue; // ignora arquivos que não são imagem suportada
            }

            $preparo = $this->prepararImagem($tmp, $mimeOriginal);
            if ($preparo === null) {
                continue;
            }

            [$base64, $mime] = $preparo;
            $imagensBase64[] = ['mime' => $mime, 'data' => $base64];
            $content[] = ["type" => "image_url", "image_url" => ["url" => "data:$mime;base64,$base64"]];
        }

        if (empty($imagensBase64)) {
            $this->errorResponse("Nenhuma imagem válida (PNG/JPEG/WEBP) foi enviada.");
        }

        // ==== MODO DEV: pula a chamada paga, testa o resto do fluxo de graça ====
        if ($devMode) {
            $diagnosticoGerado = "### Resumo técnico\n- [DEV_MODE] Laudo simulado, nenhuma chamada à OpenAI foi feita.";
        } else {
            $diagnosticoGerado = $this->chamarOpenAI($apiKey, $content);
        }

        $laudoFormatado = $this->formatarDiagnostico($diagnosticoGerado);

        $resultId = $this->model->create([
            "instituicao_id" => $usuario['instituicao_id'],
            "paciente_id"    => $paciente['id'],
            "nome"           => $paciente['nome'],
            "cpf"            => $paciente['cpf'],
            "imagem"         => json_encode($imagensBase64),
            "diagnostico"    => $laudoFormatado,
            "data_diagnostico" => date('Y-m-d'),
        ]);

        if (!$resultId) {
            $this->errorResponse("Erro ao salvar no banco.", 500);
        }

        $this->registrarAuditoria('Gerou diagnóstico de IA', "Paciente ID: {$paciente['id']}", $paciente['id']);

        $this->jsonResponse([
            "id"         => $resultId,
            "diagnostico" => $laudoFormatado,
            "imagens"    => $imagensBase64,
        ], true, 201);
    }

    public function list()
    {
        $usuario = $GLOBALS['usuario'];
        $data = $this->model->getAll($usuario['instituicao_id']);
        $this->jsonResponse($data);
    }

    public function listByPaciente($pacienteId)
    {
        $usuario = $GLOBALS['usuario'];
        $data = $this->model->getByPaciente($pacienteId, $usuario['instituicao_id']);
        $this->jsonResponse($data);
    }

    public function delete($id)
    {
        $usuario = $GLOBALS['usuario'];
        $this->model->delete($id, $usuario['instituicao_id']);
        $this->registrarAuditoria('Deletou Exame IA', "Excluiu diagnóstico de IA ID: " . $id);
        $this->jsonResponse(["msg" => "Registro de IA removido com sucesso"]);
    }

    // ==== HELPERS PRIVADOS ====

    /**
     * Redimensiona (se necessário) e retorna [base64, mime] para enviar à OpenAI e salvar no banco.
     * Não salva nenhum arquivo em disco.
     */
    private function prepararImagem(string $caminhoTmp, string $mimeOriginal): ?array
    {
        [$largura, $altura] = getimagesize($caminhoTmp);
        $ladoMaior = max($largura, $altura);

        // Imagem já está dentro do limite — converte direto para base64
        if ($ladoMaior <= self::MAX_LADO_PX) {
            return [base64_encode(file_get_contents($caminhoTmp)), $mimeOriginal];
        }

        // Precisa redimensionar
        $escala = self::MAX_LADO_PX / $ladoMaior;
        $novaLargura = (int) round($largura * $escala);
        $novaAltura  = (int) round($altura * $escala);

        $origem = match ($mimeOriginal) {
            'image/jpeg' => imagecreatefromjpeg($caminhoTmp),
            'image/png'  => imagecreatefrompng($caminhoTmp),
            'image/webp' => imagecreatefromwebp($caminhoTmp),
            default      => null,
        };

        // Se não conseguiu criar a imagem GD, retorna o original sem redimensionar
        if (!$origem) {
            return [base64_encode(file_get_contents($caminhoTmp)), $mimeOriginal];
        }

        $destinoImg = imagecreatetruecolor($novaLargura, $novaAltura);
        imagecopyresampled($destinoImg, $origem, 0, 0, 0, 0, $novaLargura, $novaAltura, $largura, $altura);

        // Captura o JPEG redimensionado direto para memória (sem passar por disco)
        ob_start();
        imagejpeg($destinoImg, null, 85);
        $imagemBytes = ob_get_clean();

        imagedestroy($origem);
        imagedestroy($destinoImg);

        return [base64_encode($imagemBytes), 'image/jpeg'];
    }

    private function chamarOpenAI(string $apiKey, array $content): string
    {
        $data = [
            "model" => "gpt-4o",
            "messages" => [
                ["role" => "system", "content" => $this->promptSistema()],
                ["role" => "user", "content" => $content],
            ],
            "max_tokens" => 1000,
        ];

        $ch = curl_init("https://api.openai.com/v1/chat/completions");
        curl_setopt_array($ch, [
            CURLOPT_POST            => true,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_HTTPHEADER      => [
                "Content-Type: application/json",
                "Authorization: Bearer $apiKey",
            ],
            CURLOPT_POSTFIELDS      => json_encode($data),
            CURLOPT_TIMEOUT         => 60,
            CURLOPT_CONNECTTIMEOUT  => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErro  = curl_error($ch);
        curl_close($ch);

        if ($response === false || $curlErro) {
            $this->errorResponse("Erro ao se conectar à API da OpenAI: $curlErro", 502);
        }

        if ($httpCode !== 200) {
            $this->errorResponse("OpenAI retornou HTTP $httpCode.", 502);
        }

        $resposta = json_decode($response, true);

        if (!isset($resposta['choices'][0]['message']['content'])) {
            $this->errorResponse("A resposta da OpenAI não tem o conteúdo esperado.", 500);
        }

        return $resposta['choices'][0]['message']['content'];
    }

    private function promptSistema(): string
    {
        return <<<EOT
#PERSONA
Sou um médico especializado em diagnóstico por imagem com foco em esclerose múltipla. Sua função será me auxiliar na identificação de indícios radiológicos de esclerose múltipla em exames de ressonância magnética (MRI).
Você atuará como um assistente clínico técnico, oferecendo uma pré-análise com alto grau de sensibilidade, servindo como apoio diagnóstico.

#OBJETIVO
Você deve ser capaz de analisar imagens de exames enviadas por profissionais de saúde e detectar sinais de indicios de Esclerose Multipla (lesões desmielinizantes) e gerar um relatório técnico de pré-análise sobre possíveis evidências de
lesões associadas à esclerose múltipla **informando obrigatoriamente o estágio provável da doença**(inicial, intermediário ou avançado) O relatório tem caráter de apoio diagnóstico e deve auxiliar o médico em sua avaliação clínica.

#FUNCIONALIDADES
- Processar e interpretar imagens de ressonância magnética.
- Identificar lesões compatíveis com EM segundo os principais padrões radiológicos;
- Interpretar sequências T1, T2, FLAIR, T1 pós-contraste (gadolínio) e outros;
- Detectar lesões hiperintensas, hipointensas, realces sutis e áreas sugestivas de desmielinização;
- Avaliar a disseminação no tempo (DNT) e no espaço (DNE) conforme os critérios de McDonald (2017);
- Indicar o estágio provável da EM, com base nos achados:
- **Estágio Inicial/Precoce**: poucas lesões localizadas, sem realce, sem DNT clara;
- **Estágio Intermediário**: múltiplas lesões em diferentes regiões, com ou sem realce, possível progressão;
- **Estágio Avançado**: lesões difusas, atrofia cerebral, realce evidente, DNT e DNE bem estabelecidas.

#REGRAS
- Nunca afirme um diagnóstico definitivo.
- Nunca forneça informações clínicas sem embasamento na imagem analisada.
- Não interaja diretamente com pacientes ou responda perguntas externas ao escopo clínico.
- Caso a imagem esteja corrompida, em baixa resolução ou fora do protocolo adequado, informe isso ao médico no relatório.
- Sempre que houver incerteza, indique-a claramente no relatório.

## ESTRUTURA DO RELATÓRIO
- **Tipo de exame, sequencia e cortes:** Ressonância Magnética do cérebro com cortes axiais, coronais e sagitais com sequencia t1, t2, flair.
- **Aspectos visíveis:** Lesões detectadas, localização, características e realce.
- **Possível relação com esclerose múltipla:** Indique se os achados são sugestivos de EM, considerando DNT e DNE.
- **Resumo técnico:** Resuma os pontos principais do exame, sequencia e achados encontrados.

#IDIOMA
Sempre responda em português do Brasil.
EOT;
    }

    private function formatarDiagnostico(string $texto): string
    {
        $linhas = preg_split('/\R/', trim($texto));
        $htmlPartes = [];
        $emLista = false;

        foreach ($linhas as $linha) {
            $linha = trim($linha);

            if ($linha === '') {
                if ($emLista) {
                    $htmlPartes[] = '</ul>';
                    $emLista = false;
                }
                $htmlPartes[] = '<br><br>';
                continue;
            }

            if (preg_match('/^#{3}\s*(.+)$/u', $linha, $m)) {
                if ($emLista) {
                    $htmlPartes[] = '</ul>';
                    $emLista = false;
                }
                $linha = '<h3>' . $m[1] . '</h3>';
            } else {
                if (preg_match('/^- +(.+)/u', $linha, $m)) {
                    if (!$emLista) {
                        $htmlPartes[] = '<ul>';
                        $emLista = true;
                    }
                    $linha = '<li>' . $m[1] . '</li>';
                } else {
                    if ($emLista) {
                        $htmlPartes[] = '</ul>';
                        $emLista = false;
                    }
                    if (!empty($htmlPartes) && substr(end($htmlPartes), -4) !== '<br>') {
                        $htmlPartes[] = '<br>';
                    }
                }
            }

            $linha = preg_replace('/\*\*(.+?)\*\*/us', '<b>$1</b>', $linha);
            $linha = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/us', '<i>$1</i>', $linha);
            $linha = strip_tags($linha, '<b><i><h3><br><ul><li>');

            $htmlPartes[] = $linha;
        }

        if ($emLista) {
            $htmlPartes[] = '</ul>';
        }

        $html = implode('', $htmlPartes);
        $html = preg_replace('/(?:<br><br>)+/u', '<br><br>', $html);

        return $html;
    }
}