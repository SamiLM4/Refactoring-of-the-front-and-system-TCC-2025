<?php
require_once "modelo/adm/administrador.php";
require_once "modelo/MeuTokenJWT.php";

use Firebase\JWT\MeuTokenJWT;

// Lê o JSON enviado
$input = file_get_contents("php://input");

$vetor = explode("/", $_SERVER['REQUEST_URI']);
$id = $vetor[2];

if (!isset($id)) {
    http_response_code(400);
    echo json_encode(["status" => false, "msg" => "Campo 'id' obrigatório."]);
    exit();
}

if (!is_numeric($id) || intval($id) <= 0) {
    http_response_code(400);
    echo json_encode(["status" => false, "msg" => "ID inválido. Deve ser um número inteiro positivo."]);
    exit();
}


$adm = new Adm();
$adm->setid(intval($id));


$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(["status" => false, "msg" => "Cabeçalho 'Authorization' ausente."]);
    exit();
}

$autorization = $headers['Authorization'];
$meutoken = new MeuTokenJWT();


if (!$meutoken->validarToken($autorization)) {
    http_response_code(401);
    echo json_encode(["status" => false, "msg" => "Token inválido ou expirado."]);
    exit();
}

$payloadRecuperado = $meutoken->getPayload();
$instituicao = $payloadRecuperado->instituicao;

if (!isset($payloadRecuperado->papel) || $payloadRecuperado->papel !== "adm") {
    http_response_code(403);
    echo json_encode([
        "cod" => 403,
        "msg" => "Acesso negado. Apenas administradores podem realizar esta operação."
    ]);
    exit();
}

$adm->setinstituicao($instituicao);

if ($adm->delete()) {
    echo json_encode(["status" => true, "msg" => "Administrador deletado com sucesso"]);
} else {
    http_response_code(500);
    echo json_encode(["status" => false, "msg" => "Erro ao deletar administrador"]);
}
?>