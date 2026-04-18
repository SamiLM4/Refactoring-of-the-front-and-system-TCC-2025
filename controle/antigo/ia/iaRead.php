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

        // Definir cabeçalho de resposta JSON
        header("Content-Type: application/json");

        // Lê o corpo JSON da requisição
        $input = json_decode(file_get_contents("php://input"), true);

        // Verifica se o CPF foi enviado
        if (!isset($input['cpf'])) {
            http_response_code(400);
            echo json_encode([
                "cod" => 400,
                "msg" => "CPF não informado no corpo JSON."
            ]);
            exit;
        }
    */

    $vetor = explode("/", $_SERVER['REQUEST_URI']);
    $cpf = $vetor[2];

    $ia = new IAResultado();
    $ia->setCpf($cpf);

    $resultado = $ia->readCPF();

    if ($resultado === null) {
        http_response_code(404);
        echo json_encode([
            "cod" => 404,
            "msg" => "Nenhum diagnóstico encontrado para o CPF informado."
        ]);
    } elseif ($resultado === false) {
        http_response_code(500);
        echo json_encode([
            "cod" => 500,
            "msg" => "Erro ao buscar o diagnóstico."
        ]);
    } else {

//        $imagemBase64 = $resultado->getImagem();
//        $imagem = base64_decode($imagemBase64);

//        header('Content-Type: image/png');
//        echo base64_decode($resultado->getImagem());

        echo json_encode([
            "cod" => 200,
            "msg" => "Diagnóstico encontrado.",
            "dados" => [
                "cpf" => $resultado->getCpf(),
                "nome" => $resultado->getNome(),
//                "imagem" => $resultado->getImagem(),
                "diagnostico" => $resultado->getDiagnostico(),
                "data do diagnostico" => $resultado->getData()
            ]
        ]);
    }
}