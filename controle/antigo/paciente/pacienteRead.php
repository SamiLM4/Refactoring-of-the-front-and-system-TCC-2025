<?php

require_once "modelo/Paciente/Paciente.php";
require_once "modelo/MeuTokenJWT2.php";

$headers = getallheaders();

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


    $paciente = new Paciente();
    $paciente->setinstituicao($payloadRecuperado->instituicao);
    $dados = $paciente->read($pagina);

    header("Content-Type: application/json");

    if ($dados && $dados["status"] === true) {
        header("HTTP/1.1 200 OK");
        echo json_encode([
            "cod" => 200,
            "msg" => $dados["msg"],
            "pacientes" => $dados["pacientes"],
            "total" => $dados["total"]
        ]);
    } else {
        header("HTTP/1.1 204 No Content");
        echo json_encode([
            "cod" => 204,
            "msg" => "Nenhum paciente encontrado.",
            "pacientes" => [],
            "total" => 0
        ]);
    }

} else {
    header("HTTP/1.1 401 Unauthorized");
    echo json_encode(["mensagem" => "Token inválido!"]);
}

?>