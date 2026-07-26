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

use Firebase\JWT\MeuTokenJWT;

require_once "modelo/MeuTokenJWT.php";

try {

    $vetor = explode("/", $_SERVER['REQUEST_URI']);
    $cpf = strip_tags(trim($vetor[2]));

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

    if ($payloadRecuperado->papel == "medico") {
        if (!isset($payloadRecuperado->cpf_medico)) {
            http_response_code(400);
            echo json_encode(["cod" => 400, "msg" => "Token inválido: cpf_medico ausente."]);
            exit();
        }

    }


    $jsonRecebidoBodyRequest = file_get_contents('php://input');
    $dados = json_decode($jsonRecebidoBodyRequest);


    $sintomas = new Sintomas();
    $sintomas->setCpf($cpf);

    $diagnostico = new Diagnostico();
    $diagnostico->setCpf($cpf);

    $exame = new ExameFisico();
    $exame->setCpf($cpf);

    $exames = new ExamesComplementares();
    $exames->setCpf($cpf);

    $historico = new HistoricoMedico();
    $historico->setCpf($cpf);

    $historicoSocial = new HistoricoSocial();
    $historicoSocial->setCpf($cpf);

    $planoTratamento = new PlanoTratamento();
    $planoTratamento->setCpf($cpf);

    $qualidadeVida = new QualidadeVidaEm();
    $qualidadeVida->setCpf($cpf);


    $relacao = new Relacao();
    $relacao->setcpfpaciente($cpf);

    $deletadoComSucesso = (
        $qualidadeVida->delete() &&
        $planoTratamento->delete() &&
        $historicoSocial->delete() &&
        $historico->delete() &&
        $exames->delete() &&
        $exame->delete() &&
        $diagnostico->delete() &&
        $sintomas->delete() &&
        $relacao->deleteCPFpaciente()
    );

    if ($deletadoComSucesso) {
        http_response_code(200);
        echo json_encode(["cod" => 200, "msg" => "Ficha deletada com sucesso!"]);
    } else {
        http_response_code(500);
        echo json_encode(["cod" => 500, "msg" => "Erro ao deletar a ficha."]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["cod" => 500, "msg" => "Erro interno: " . $e->getMessage()]);
}
?>