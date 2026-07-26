<?php
require_once "modelo/Paciente/Paciente.php";
require_once "modelo/Medico/Medico.php";
require_once "modelo/MeuTokenJWT.php";
//require_once "modelo/medico/RelacaoMedicoPaciente.php";

use Firebase\JWT\MeuTokenJWT;

header("Content-Type: application/json");

// Verifica se o método é PUT
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode([
        "cod" => 0,
        "msg" => "Método não permitido. Use PUT para atualizar paciente."
    ]);
    exit();
}

// Recupera o token
$headers = getallheaders();
$authorization = $headers['Authorization'] ?? '';

$token = new MeuTokenJWT();
if (!$token->validarToken($authorization)) {
    http_response_code(401);
    echo json_encode([
        "cod" => 401,
        "msg" => "Token inválido ou ausente."
    ]);
    exit();
}

$payload = $token->getPayload();

// Verifica o papel do usuário
if (!isset($payload->papel) ) {
    http_response_code(403);
    echo json_encode([
        "cod" => 403,
        "msg" => "Acesso negado. Apenas médicos podem atualizar pacientes."
    ]);
    exit();
}

// Lê e decodifica o corpo da requisição
$jsonBody = file_get_contents('php://input');
$obj = json_decode($jsonBody);

// Recupera CPF da URL
$uriParts = explode("/", $_SERVER['REQUEST_URI']);
$cpf = $uriParts[2] ?? '';
$cpf = strip_tags($cpf);

// Valida o CPF
if (empty($cpf)) {
    http_response_code(400);
    echo json_encode(["cod" => 1, "msg" => "CPF não pode ser vazio."]);
    exit();
} elseif (!preg_match("/^\d{11}$/", $cpf)) {
    http_response_code(400);
    echo json_encode(["cod" => 2, "msg" => "CPF inválido. Deve conter 11 dígitos numéricos."]);
    exit();
}

// Extrai e sanitiza os dados recebidos
$nome             = isset($obj->nome)             ? strip_tags($obj->nome)             : '';
$sexo             = isset($obj->sexo)             ? strip_tags($obj->sexo)             : '';
$endereco         = isset($obj->endereco)         ? strip_tags($obj->endereco)         : '';
$telefone         = isset($obj->telefone)         ? strip_tags($obj->telefone)         : '';
$email            = isset($obj->email)            ? strip_tags($obj->email)            : '';
$profissao        = isset($obj->profissao)        ? strip_tags($obj->profissao)        : '';
$estado_civil     = isset($obj->estado_civil)     ? strip_tags($obj->estado_civil)     : '';
$nome_cuidador    = isset($obj->nome_cuidador)    ? strip_tags($obj->nome_cuidador)    : '';
$telefone_cuidador= isset($obj->telefone_cuidador)? strip_tags($obj->telefone_cuidador): '';

// Valida o e-mail
if (empty($email)) {
    http_response_code(400);
    echo json_encode(["cod" => 3, "msg" => "E-mail não pode ser vazio."]);
    exit();
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["cod" => 4, "msg" => "Formato de e-mail inválido."]);
    exit();
}

// Cria e configura o objeto Paciente
$paciente = new Paciente();
$paciente->setNome($nome);
$paciente->setCpf($cpf);
$paciente->setSexo($sexo);
$paciente->setEndereco($endereco);
$paciente->setTelefone($telefone);
$paciente->setEmail($email);
$paciente->setProfissao($profissao);
$paciente->setEstadoCivil($estado_civil);
$paciente->setNomeCuidador($nome_cuidador);
$paciente->setTelefoneCuidador($telefone_cuidador);
$paciente->setinstituicao($payload->instituicao);

//$relacaoMedicoPaciente = new RelacaoMedicoPaciente();
//$relacaoMedicoPaciente->readByCpfMedico($payload->cpf);



// Executa o update
$resultado = $paciente->update();

if ($resultado === true) {
    http_response_code(200);
    echo json_encode([
        "cod" => 200,
        "msg" => "Paciente atualizado com sucesso."
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "cod" => 5,
        "msg" => "Erro ao atualizar paciente. Verifique os dados ou tente novamente mais tarde."
    ]);
}
