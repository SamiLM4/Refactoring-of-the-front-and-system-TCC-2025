<?php
use Firebase\JWT\MeuTokenJWT;
require_once "modelo/Medico/Medico.php";

require_once "modelo/MeuTokenJWT.php";
$headers = getallheaders();
$autorization = $headers['Authorization'];
$meutoken = new MeuTokenJWT();

$vetor = explode("/", $_SERVER['REQUEST_URI']);
$pagina = $vetor[2];

if ($meutoken->validarToken($autorization) == true) {
    $payloadRecuperado = $meutoken->getPayload();
    $instituicao = $payloadRecuperado->instituicao;

    $medico = new Medico();
    $medico->setinstituicao($instituicao);
    $medicos = $medico->read($pagina);

    header("Content-Type: application/json");
    if ($medicos) {
        header("HTTP/1.1 200 OK");


        echo json_encode([$medicos]);

    } else {
        header("HTTP/1.1 404 Not Found");
        echo json_encode(["mensagem" => "Nenhum Medico encontrado."]);
    }


} else {

    header("HTTP/1.1 404 Not Found");
    echo json_encode(["mensagem" => "Erro"]);

}


?>