<?php
use Firebase\JWT\MeuTokenJWT;
require_once "modelo/MeuTokenJWT.php";
require_once "modelo/Mensagem/Mensagem.php";
require_once "modelo/Medico/Medico.php";
$headers = getallheaders();
$autorization = $headers['Authorization'];
$meutoken = new MeuTokenJWT();

if (!isset($headers['Authorization'])) {
    respostaErro(401, 10, "Cabeçalho 'Authorization' ausente.");
}

$body = json_decode(file_get_contents('php://input'));

if ($meutoken->validarToken($autorization) == true) {
    $payloadRecuperado = $meutoken->getPayload();

if (!isset($body->mensagem) || trim($body->mensagem) === '') {
    http_response_code(400);
    echo json_encode(["msg" => "Mensagem não pode estar vazia."]);
    exit();
}
$uriParts = explode("/", $_SERVER['REQUEST_URI']);
$mensagem = new Mensagem();
$mensagem->setCpfMedico($uriParts[3]);
$mensagem->setMensagem(trim($body->mensagem));
$mensagem->setOrigem($body->origem);
$mensagem->setInstituicao($payloadRecuperado->instituicao);

if ($mensagem->enviar()) {
    echo json_encode([
        "cod" => 201,
        "msg" => "Mensagem enviada com sucesso!"
    ]);
} else {
    http_response_code(500);
    echo json_encode(["msg" => "Erro ao enviar mensagem."]);
}
}

?>
