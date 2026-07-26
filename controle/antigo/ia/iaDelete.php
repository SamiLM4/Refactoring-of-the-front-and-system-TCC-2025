<?php
require_once __DIR__ . "/../../modelo/ia/ia.php";


use Firebase\JWT\MeuTokenJWT;
require_once "modelo/MeuTokenJWT.php";

$headers = getallheaders();
$autorization = $headers['Authorization'] ?? null;

$meutoken = new MeuTokenJWT();

if ($meutoken->validarToken($autorization)) {
    $payloadRecuperado = $meutoken->getPayload();
    /*
        // Mostrar erros
        ini_set('display_errors', 1);
        error_reporting(E_ALL);

        // Verifica se o CPF foi enviado
        if (!isset($_POST['cpf'])) {
            http_response_code(400);
            echo json_encode([
                "cod" => 400,
                "msg" => "CPF não fornecido para exclusão."
            ]);
            exit;
        }
    */
    
    $vetor = explode("/", $_SERVER['REQUEST_URI']);
    $cpf = $vetor[2];


    // Cria objeto e tenta excluir
    $ia = new IAResultado();
    $ia->setCpf($cpf);

    if ($ia->delete()) {
        echo json_encode([
            "cod" => 200,
            "msg" => "Diagnóstico excluído com sucesso."
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "cod" => 500,
            "msg" => "Erro ao excluir o diagnóstico."
        ]);
    }
}