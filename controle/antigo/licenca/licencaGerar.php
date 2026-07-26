<?php
require_once "modelo/MeuTokenJWT2.php";

use Firebase\JWT\MeuTokenJWT2;

// Recebe o JSON do cliente
$input = file_get_contents("php://input");
$dados = json_decode($input);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(["status" => false, "msg" => "JSON inválido."]);
    exit();
}

// Verifica campos obrigatórios
if (!isset($dados->email, $dados->tipo_licenca)) {
    http_response_code(400);
    echo json_encode(["status" => false, "msg" => "Campos obrigatórios ausentes: email, tipo_licenca."]);
    exit();
}

$payload = new stdClass();
$payload->email = trim($dados->email);
$payload->tipo_licenca = trim($dados->tipo_licenca);
$payload->status = "ativa";
$payload->exp = time() + 3600 * 24 * 30 * 12; // token válido por 12 meses

$meutoken = new MeuTokenJWT2();
$token = $meutoken->gerarToken($payload);

// Registra o token no banco
if ($meutoken->registrarToken($token, $payload)) {
    http_response_code(201);
    echo json_encode([
        "status" => true,
        "msg" => "Token de licença gerado com sucesso.",
        "token" => $token,
        "expira_em" => date('Y-m-d H:i:s', $payload->exp)
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "status" => false,
        "msg" => "Erro ao registrar token no banco."
    ]);
}
