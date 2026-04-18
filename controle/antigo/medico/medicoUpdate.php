<?php

use Firebase\JWT\MeuTokenJWT;
require_once "modelo/Medico/Medico.php";
require_once "modelo/MeuTokenJWT.php";

// Obter cabeçalhos
$headers = getallheaders();
$authorization = $headers['Authorization'] ?? null;

if (!$authorization) {
    http_response_code(401);
    echo json_encode([
        "cod" => 401,
        "msg" => "Token de autorização não fornecido."
    ]);
    exit();
}

$meutoken = new MeuTokenJWT();

// Verificar validade do token
if (!$meutoken->validarToken($authorization)) {
    http_response_code(401);
    echo json_encode([
        "cod" => 401,
        "msg" => "Token inválido ou expirado."
    ]);
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

// Receber dados do corpo da requisição
$jsonRecebidoBodyRequest = file_get_contents('php://input');
$obj = json_decode($jsonRecebidoBodyRequest);

// Validar se o corpo é válido
if (!$obj) {
    respostaErro(400, 0, "JSON inválido ou não fornecido.");
}

// Extrair e limpar os dados
$vetor = explode("/", $_SERVER['REQUEST_URI']);
$cpfMedico = strip_tags($vetor[2] ?? '');
$crmMedico = strip_tags($obj->crm ?? '');
$emailMedico = strip_tags($obj->email ?? '');
$senhaMedico = strip_tags($obj->senha ?? '');
$nomeMedico = strip_tags($obj->nome ?? '');

// Validações de campos obrigatórios
if (empty($nomeMedico)) {
    respostaErro(400, 1, "Nome não pode ser vazio.");
}
if (empty($crmMedico)) {
    respostaErro(400, 1, "CRM não pode ser vazio.");
}
if (empty($emailMedico)) {
    respostaErro(400, 2, "Email não pode ser vazio.");
}
if (empty($senhaMedico)) {
    respostaErro(400, 3, "Senha não pode ser vazia.");
}
if (empty($cpfMedico)) {
    respostaErro(400, 4, "CPF não pode ser vazio.");
}
if (empty($crmMedico)) {
    respostaErro(400, 5, "CRM não pode ser vazio.");
}

// Validação de CPF
if (!preg_match('/^\d{11}$/', $cpfMedico)) {
    respostaErro(400, 6, "CPF inválido. Deve conter exatamente 11 dígitos.");
}

if (!filter_var($emailMedico, FILTER_VALIDATE_EMAIL)) {
    respostaErro(400, 8, "Email inválido.");
}

// Instanciar e atualizar o médico
$medico = new Medico();
$medico->setCpf($cpfMedico);
$medico->setCrm($crmMedico);
$medico->setEmail($emailMedico);
$medico->setSenha($senhaMedico);
$medico->setNome($nomeMedico);
$medico->setinstituicao($payloadRecuperado->instituicao);

$resultado = $medico->update();

if ($resultado === true) {
    http_response_code(200);
    echo json_encode([
        "cod" => 200,
        "msg" => "Médico atualizado com sucesso!"
    ]);
} elseif ($resultado === false) {
    // Erro ao executar update (ex: erro no banco)
    respostaErro(500, 9, "Erro ao atualizar o médico.");
} elseif ($resultado === null) {
    // CPF não encontrado
    respostaErro(404, 10, "CPF informado não encontrado.");
} else {
    // Caso imprevisto
    respostaErro(500, 11, "Erro inesperado.");
}

exit();

// Função auxiliar de erro
function respostaErro($httpCode, $codErro, $mensagem)
{
    http_response_code($httpCode);
    echo json_encode([
        "cod" => $codErro,
        "msg" => $mensagem
    ]);
    exit();
}
?>
