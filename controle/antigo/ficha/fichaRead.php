<?php
require_once "modelo/ficha/Sintomas.php";
require_once "modelo/ficha/Diagnostico.php";
require_once "modelo/ficha/ExameFisico.php";
require_once "modelo/ficha/ExamesComplementares.php";
require_once "modelo/ficha/HistoricoMedico.php";
require_once "modelo/ficha/HistoricoSocial.php";
require_once "modelo/ficha/PlanoTratamento.php";
require_once "modelo/ficha/QualidadeVidaEm.php";
require_once "modelo/Medico/Medico.php";
require_once "modelo/Medico/RelacaoMedicoPaciente.php";

header('Content-Type: application/json');

use Firebase\JWT\MeuTokenJWT;
require_once "modelo/MeuTokenJWT.php";

// Recuperar CPF da URL
$vetor = explode("/", $_SERVER['REQUEST_URI']);
$cpf = end($vetor);
$cpf = strip_tags(trim($cpf));

if (!$cpf || empty($cpf)) {
    http_response_code(400);
    echo json_encode(["cod" => 400, "msg" => "CPF não informado na URL"]);
    exit();
}

// Autenticação via Token
$headers = getallheaders();
if (!isset($headers['Authorization']) || empty(trim($headers['Authorization']))) {
    http_response_code(401);
    echo json_encode(["cod" => 401, "msg" => "Token de autorização ausente."]);
    exit();
}

$autorization = trim($headers['Authorization']);
$meutoken = new MeuTokenJWT();

if (!$meutoken->validarToken($autorization)) {
    http_response_code(401);
    echo json_encode(["cod" => 401, "msg" => "Token inválido."]);
    exit();
}

$payloadRecuperado = $meutoken->getPayload();
/*
if (!isset($payloadRecuperado->cpf_medico)) {
    http_response_code(400);
    echo json_encode(["cod" => 400, "msg" => "Token inválido: cpf_medico ausente."]);
    exit();
}
*/
$medicos = null;

try {
    $sintomas = new Sintomas();
    $sintomas->setCpf($cpf);
    $dadosSintomas = $sintomas->readCPF();

    $diagnostico = new Diagnostico();
    $diagnostico->setCpf($cpf);
    $dadosDiagnostico = $diagnostico->readCPF();

    $exame = new ExameFisico();
    $exame->setCpf($cpf);
    $dadosExame = $exame->readCPF();

    $exames = new ExamesComplementares();
    $exames->setCpf($cpf);
    $dadosExames = $exames->readCPF();

    $historico = new HistoricoMedico();
    $historico->setCpf($cpf);
    $dadosHistorico = $historico->readCPF();

    $historicoSocial = new HistoricoSocial();
    $historicoSocial->setCpf($cpf);
    $dadosHistoricoSocial = $historicoSocial->readCPF();

    $planoTratamento = new PlanoTratamento();
    $planoTratamento->setCpf($cpf);
    $dadosPlano = $planoTratamento->readCPF();

    $qualidadeVida = new QualidadeVidaEm();
    $qualidadeVida->setCpf($cpf);
    $dadosQualidade = $qualidadeVida->readCPF();

    $relacao = new Relacao();
    $relacao->setcpfpaciente($cpf);
    $medicos = $relacao->readCPFpaciente();

    if (is_array($medicos) && count($medicos) > 0 && is_object($medicos[0])) {
        $cpfMedico = $medicos[0]->getcpfmedico();

        $medico = new Medico();
        $medico->setcpf($cpfMedico);
        $medico->setinstituicao($payloadRecuperado->instituicao);
        $medicos = $medico->readCPF();

        $medicoResponsavelNome = !empty($medicos) && is_array($medicos) && is_object($medicos[0])
            ? $medicos[0]->getNome()
            : "Médico não encontrado";


    } else {
        $medicoResponsavelNome = "Nenhuma relação médico-paciente encontrada";
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["cod" => 500, "msg" => "Erro interno: " . $e->getMessage()]);
    exit();
}

echo json_encode([
    "cod" => 200,
    "msg" => "Dados recuperados com sucesso!",
    "dados" => [
        "Medico_Responsavel" => $medicoResponsavelNome,
        "sintomas" => $dadosSintomas ? $dadosSintomas->toArray() : null,
        "diagnostico" => $dadosDiagnostico ? $dadosDiagnostico->toArray() : null,
        "exame_fisico" => $dadosExame ? $dadosExame->toArray() : null,
        "exames_complementares" => $dadosExames ? $dadosExames->toArray() : null,
        "historico_medico" => $dadosHistorico ? $dadosHistorico->toArray() : null,
        "historico_social" => $dadosHistoricoSocial ? $dadosHistoricoSocial->toArray() : null,
        "plano_tratamento" => $dadosPlano ? $dadosPlano->toArray() : null,
        "qualidade_vida_em" => $dadosQualidade ? $dadosQualidade->toArray() : null
    ]
]);
