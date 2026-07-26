<?php
use Firebase\JWT\MeuTokenJWT;
require_once "modelo/MeuTokenJWT.php";
require_once "modelo/Mensagem/Mensagem.php";
require_once "modelo/Medico/Medico.php";

$vetor = explode("/", $_SERVER['REQUEST_URI']);
$metodo = $_SERVER['REQUEST_METHOD'];

$headers = getallheaders();
$autorization = $headers['Authorization'];
$meutoken = new MeuTokenJWT();

if ($meutoken->validarToken($autorization) == true) {
    $payloadRecuperado = $meutoken->getPayload();

    if ($metodo == "GET") {
        $cpf_medico = $vetor[3];

        $mensagem = new Mensagem();
        $mensagem->setInstituicao($payloadRecuperado->instituicao);
        $mensagem->setCpfMedico($cpf_medico);
    $mensagens = $mensagem->listarMensagensMedico($cpf_medico);

if ($mensagens) {
    header("HTTP/1.1 200 OK");
    echo json_encode([
        "cod" => 200,
        "msg" => "Mensagens encontradas",
        "mensagens" => $mensagens
    ]);
} else {
    header("HTTP/1.1 404 Not Found");
    echo json_encode([
        "cod" => 404,
        "msg" => "Nenhuma mensagem encontrada"
    ]);
}
    }
}
?>