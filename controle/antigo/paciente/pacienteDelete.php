<?php
require_once "modelo/Paciente/Paciente.php";
require_once "modelo/MeuTokenJWT.php";
require_once "modelo/Medico/Medico.php";

use Firebase\JWT\MeuTokenJWT;

// Função auxiliar para enviar erro e encerrar
function respostaErro($httpCode, $codErro, $mensagem) {
    http_response_code($httpCode);
    echo json_encode([
        "cod" => $codErro,
        "msg" => $mensagem
    ]);
    exit();
}

// Verifica o método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    header("Allow: DELETE");
    respostaErro(405, 1, "Método não permitido. Use DELETE.");
}

// Valida token
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    respostaErro(401, 2, "Cabeçalho de autorização ausente.");
}

$tokenJWT = new MeuTokenJWT();
$token = $headers['Authorization'];

if (!$tokenJWT->validarToken($token)) {
    respostaErro(401, 3, "Token inválido.");
}

// Extrai CPF da URI
$partes = explode("/", $_SERVER['REQUEST_URI']);
$cpf = end($partes); // assume que o CPF está no final da URL

if (!preg_match('/^\d{11}$/', $cpf)) {
    respostaErro(400, 5, "CPF inválido. Deve conter 11 dígitos numéricos.");
}

// Instancia o paciente e tenta excluir
$paciente = new Paciente();
$paciente->setCpf($cpf);
$paciente->setinstituicao($tokenJWT->getPayload()->instituicao);

if ($paciente->delete()) {
    http_response_code(204); // No Content
    exit();
} else {
    respostaErro(500, 6, "Erro interno ao tentar excluir o paciente.");
}
?>
