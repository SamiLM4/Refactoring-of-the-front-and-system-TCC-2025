<?php
require_once "modelo/adm/administrador.php";
require_once "modelo/MeuTokenJWT.php";

use Firebase\JWT\MeuTokenJWT;

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

if (!isset($payloadRecuperado->papel) || $payloadRecuperado->papel !== "adm") {
    http_response_code(403);
    echo json_encode([
        "cod" => 403,
        "msg" => "Acesso negado. Apenas administradores podem realizar esta operação."
    ]);
    exit();
}

$input = file_get_contents("php://input");
$dados = json_decode($input);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(["status" => false, "msg" => "JSON inválido."]);
    exit();
}

$camposObrigatorios = ['id', 'nome', 'email', 'senha'];
foreach ($camposObrigatorios as $campo) {
    if (!isset($dados->$campo) || empty($dados->$campo)) {
        http_response_code(400);
        echo json_encode(["status" => false, "msg" => "Campo obrigatório '$campo' ausente ou vazio."]);
        exit();
    }
}

$instituicao = $payloadRecuperado->instituicao;

$id = $dados->id;
$nome = trim($dados->nome);
$email = $dados->email;
$senha = $dados->senha;

if (!is_numeric($id) || intval($id) <= 0) {
    http_response_code(400);
    echo json_encode(["status" => false, "msg" => "ID inválido. Deve ser um número inteiro positivo."]);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["status" => false, "msg" => "Email inválido."]);
    exit();
}

if (strlen($senha) < 4) {
    http_response_code(400);
    echo json_encode(["status" => false, "msg" => "A senha deve conter ao menos 4 caracteres."]);
    exit();
}

$adm = new Adm();
$adm->setid(intval($id));
$adm->setnome($nome);
$adm->setemail($email);
$adm->setsenha($senha);
$adm->setinstituicao($instituicao);

if ($adm->update()) {
    echo json_encode(["status" => true, "msg" => "Administrador atualizado com sucesso"]);
} else {
    http_response_code(500);
    echo json_encode(["status" => false, "msg" => "Erro ao atualizar administrador"]);
}
?>
