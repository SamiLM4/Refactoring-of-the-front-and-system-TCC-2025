<?php

require_once __DIR__ . "/../../modelo/ia/ia.php";
use Firebase\JWT\MeuTokenJWT;
require_once "modelo/MeuTokenJWT.php";

header("Content-Type: application/json");

$headers = getallheaders();
$autorization = $headers['Authorization'] ?? null;

$meutoken = new MeuTokenJWT();

if ($meutoken->validarToken($autorization)) {
    $payloadRecuperado = $meutoken->getPayload();

    $cpf = $_POST['cpf'] ?? null;

    $imagensTmp = $_FILES['imagens']['tmp_name'] ?? [];

    if (!is_array($imagensTmp)) {
        $imagensTmp = [$imagensTmp]; // transforma string em array com 1 elemento
    }

    $teste_ja_existe = new IAResultado();
    $teste_ja_existe->setCpf($cpf);

    $resultado_teste = $teste_ja_existe->readCPF();

    if ($resultado_teste !== null) {
        http_response_code(404);
        echo json_encode([
            "cod" => 400,
            "msg" => "Ja Cadastrado!"
        ]);
        exit();
    } elseif ($resultado_teste === false) {
        http_response_code(500);
        echo json_encode([
            "cod" => 500,
            "msg" => "Erro ao buscar o diagnóstico."
        ]);
        exit();
    }

    if (!$cpf || !isset($_FILES['imagens']) || empty($_FILES['imagens']['tmp_name'])) {
        http_response_code(400);
        echo json_encode(["cod" => 400, "msg" => "CPF ou imagem não enviados corretamente."]);
        exit;
    }


    $api_key = "x"; // sua chave válida

    $content = [["type" => "text", "text" => ""]];
    foreach ($imagensTmp as $tmp) {
        if ($tmp && file_exists($tmp)) {
            $mime = mime_content_type($tmp);
            $base64 = base64_encode(file_get_contents($tmp));
            $dataUrl = "data:$mime;base64,$base64";
            $content[] = ["type" => "image_url", "image_url" => ["url" => $dataUrl]];
        }
    }



    $promptSystem = <<<EOT
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

    ## EXEMPLO DE RESPOSTA:
    ### 1. 🔍 *Tipo de exame e cortes*

    * Primeira imagem → *Ressonância Magnética (RM)* do cérebro, corte *axial, provavelmente com **contraste T1*, pois há realce nas lesões e meninges.
    * Segunda imagem → *Ressonância Magnética* do cérebro, corte *sagital, sequência **T2, evidenciando lesões hiperintensas na **substância branca*.
    * Terceira imagem → *Ressonância Magnética* do cérebro, corte *axial, sequência **FLAIR* (supressão do líquor), destacando lesões na *substância branca periventricular*.

    ### 2. 👀 *Aspectos visíveis*

    * Em todas as sequências, há *múltiplas áreas hiperintensas* (brancas nas imagens T2/FLAIR) na substância branca, especialmente:
    * *Periventriculares* (próximas aos ventrículos laterais).
    * *Subcorticais* (perto da superfície do cérebro).
    * Algumas aparentam *realce pelo contraste* (primeira imagem), sugerindo *atividade inflamatória recente*.
    * Esse padrão é *compatível com desmielinização, que pode ser visto na **esclerose múltipla (EM)*, mas também pode ocorrer em outras doenças inflamatórias ou vasculares.

    ### 3. 🧠 *Possível relação com esclerose múltipla*

    * O padrão de lesões (*periventricular, ovaladas, distribuídas em diferentes cortes) é **típico da EM*.
    * A presença de lesões com *realce* indica *lesões ativas, enquanto lesões sem realce podem ser **crônicas* — sugerindo *disseminação no tempo e espaço*, um critério importante para EM.
    * Isso poderia indicar um *estágio ativo-remitente* da doença, mas para classificar exatamente (RRMS, SPMS, PPMS) é necessário histórico clínico e exames anteriores.

    ### 4. 📌 *Resumo técnico*
    * *Exame*: Ressonância Magnética de crânio.
    * *Sequências/Cortes*: Axial T1 com contraste, Sagital T2, Axial FLAIR.
    * *Achados: Múltiplas lesões **periventriculares* e *subcorticais, algumas **realçadas pelo contraste, padrão compatível com **doença desmielinizante* como *esclerose múltipla*.
    * *Sugestão*: Avaliação neurológica com base clínica e exames complementares para confirmar diagnóstico e estágio.



    #IDIOMA
    Sempre responda em português do Brasil.


EOT;

    $data = [
        "model" => "gpt-4.1",
        "messages" => [
            ["role" => "system", "content" => $promptSystem],
            ["role" => "user", "content" => $content]
        ],
        "max_tokens" => 1000
    ];

    $options = [
        "http" => [
            "method" => "POST",
            "header" => "Content-Type: application/json\r\nAuthorization: Bearer $api_key\r\n",
            "content" => json_encode($data)
        ]
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents("https://api.openai.com/v1/chat/completions", false, $context);

    if ($response === false) {
        http_response_code(500);
        echo json_encode([
            "cod" => 500,
            "msg" => "Erro ao se conectar à API da OpenAI.",
            "erro_stream" => error_get_last()
        ]);
        exit;
    }

    $resposta = json_decode($response, true);

    if (!isset($resposta['choices'][0]['message']['content'])) {
        http_response_code(500);
        echo json_encode([
            "cod" => 500,
            "msg" => "A resposta da OpenAI não tem o conteúdo esperado.",
            "resposta_crua" => $resposta
        ]);
        exit;
    }

    $diagnosticoGerado = $resposta['choices'][0]['message']['content'];

    $laudoFormatado = formatarDiagnostico($diagnosticoGerado);

    $ia = new IAResultado();
    $ia->setCpf($_POST['cpf'] ?? '');
    $ia->setDiagnostico($laudoFormatado);


    $imagens = [];
    $imagensTmp = $_FILES['imagens']['tmp_name'] ?? [];
    if (!is_array($imagensTmp))
        $imagensTmp = [$imagensTmp];

    foreach ($imagensTmp as $tmpName) {
        if ($tmpName && file_exists($tmpName)) {
            $conteudo = file_get_contents($tmpName);
            $imagens[] = base64_encode($conteudo);
        }
    }
    $ia->setImagens($imagens);


    if ($ia->cadastrar()) {
        echo json_encode([
            "cod" => 201,
            "msg" => "Diagnóstico salvo com sucesso.",
            "laudo" => formatarDiagnostico($laudoFormatado)
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["cod" => 500, "msg" => "Erro ao salvar no banco."]);
    }
}



/*
function formatarDiagnostico($texto)
{
    $linhas = explode("\n", $texto);
    $resultado = [];
    $primeiraLinha = true;

    foreach ($linhas as $linha) {
        $linha = trim($linha);

        // Insere <br><br> antes de tópicos ou divisores
        if ($linha === '' || preg_match('/^---$/', $linha)) {
            if (!empty($resultado) && end($resultado) !== '<br><br>') {
                $resultado[] = '<br><br>';
            }
            continue;
        }

        // Primeira linha recebe dois parágrafos antes
        if ($primeiraLinha) {
            $resultado[] = '<br><br><br><br>';
            $primeiraLinha = false;
        }

        // Detecta títulos com emoji e ### no início
        if (preg_match('/^(###\s*[\p{So}].*)/u', $linha, $matches)) {
            // Mantém todo o texto original do título, mas coloca em <b>
            $linhaHtml = '<b>' . $matches[1] . '</b>';
        } else {
            // Para demais linhas, mantém o texto normal
            $linhaHtml = htmlspecialchars($linha, ENT_QUOTES, 'UTF-8');
        }

        $resultado[] = $linhaHtml;
    }

    // Remove múltiplos <br><br> seguidos
    $html = implode('', $resultado);
    $html = preg_replace('/(<br><br>)+/', '<br><br>', $html);

    return $html;
}
*/

function formatarDiagnostico(string $texto): string
{
    // normaliza quebras
    $linhas = preg_split('/\R/', trim($texto));
    $htmlPartes = [];
    $emLista = false;

    foreach ($linhas as $linha) {
        $linha = trim($linha);

        // pula linhas vazias adicionando separador visual
        if ($linha === '') {
            if ($emLista) {
                $htmlPartes[] = '</ul>';
                $emLista = false;
            }
            $htmlPartes[] = '<br><br>';
            continue;
        }

        // ### Título
        if (preg_match('/^#{3}\s*(.+)$/u', $linha, $m)) {
            if ($emLista) {
                $htmlPartes[] = '</ul>';
                $emLista = false;
            }
            $linha = '<h3>' . $m[1] . '</h3>';
        } else {
            // Bullet "- "
            if (preg_match('/^- +(.+)/u', $linha, $m)) {
                if (!$emLista) {
                    $htmlPartes[] = '<ul>';
                    $emLista = true;
                }
                $linha = '<li>' . $m[1] . '</li>';
            } else {
                // linha comum -> quebra dupla entre blocos
                if ($emLista) {
                    $htmlPartes[] = '</ul>';
                    $emLista = false;
                }
                // adiciona <br> entre linhas de parágrafo
                if (!empty($htmlPartes) && substr(end($htmlPartes), -4) !== '<br>') {
                    $htmlPartes[] = '<br>';
                }
            }
        }

        // **negrito** -> <b> ; *italico* -> <i>
        $linha = preg_replace('/\*\*(.+?)\*\*/us', '<b>$1</b>', $linha);
        // cuidado para não transformar ** em duas vezes
        $linha = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/us', '<i>$1</i>', $linha);

        // permite só um subconjunto seguro de tags
        $linha = strip_tags($linha, '<b><i><h3><br><ul><li>');

        $htmlPartes[] = $linha;
    }

    if ($emLista) {
        $htmlPartes[] = '</ul>';
    }

    // compacta <br><br> repetidos
    $html = implode('', $htmlPartes);
    $html = preg_replace('/(?:<br><br>)+/u', '<br><br>', $html);

    return $html;
}

