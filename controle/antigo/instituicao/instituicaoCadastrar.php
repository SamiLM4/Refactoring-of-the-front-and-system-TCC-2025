<?php
require_once "modelo/instituicao/instituicao.php";
require_once "modelo/MeuTokenJWT2.php";

use Firebase\JWT\MeuTokenJWT2;

$input = file_get_contents("php://input");
$dados = json_decode($input);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(["status" => false, "msg" => "JSON inválido."]);
    exit();
}

if (!isset($dados->nome, $dados->CEP, $dados->cnpj, $dados->tipo, $dados->email)) {
    http_response_code(400);
    echo json_encode(["status" => false, "msg" => "Campos obrigatórios ausentes."]);
    exit();
}

// Dados da instituição
$nome = trim($dados->nome);
$CEP = trim($dados->CEP);
$logradouro = isset($dados->logradouro) ? trim($dados->logradouro) : null;
$cidade = isset($dados->cidade) ? trim($dados->cidade) : null;
$bairro = isset($dados->bairro) ? trim($dados->bairro) : null;
$cnpj = trim($dados->cnpj);
$tipo = trim($dados->tipo);
$tiposValidos = ['publico', 'privado', 'filantropico'];

if (!in_array($tipo, $tiposValidos)) {
    http_response_code(400);
    echo json_encode(["status" => false, "msg" => "Tipo inválido. Use: público, privado ou filantrópico."]);
    exit();
}

$telefone = isset($dados->telefone) ? trim($dados->telefone) : null;
$email = isset($dados->email) ? trim($dados->email) : null;
$site = isset($dados->site) ? trim($dados->site) : null;
$atividade = "ativo";
$nome_responsavel = isset($dados->nome_responsavel) ? trim($dados->nome_responsavel) : null;
$telefone_responsavel = isset($dados->telefone_responsavel) ? trim($dados->telefone_responsavel) : null;

// Verifica token de licença
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(["status" => false, "msg" => "Token de licença ausente."]);
    exit();
}

$authorization = str_replace("Bearer ", "", $headers['Authorization']);
$meutoken = new MeuTokenJWT2();

if (!$meutoken->validarToken($authorization)) {
    http_response_code(401);
    echo json_encode(["status" => false, "msg" => "Token inválido ou expirado."]);
    exit();
}

// Recupera token do banco
$licencaBanco = $meutoken->verificarToken($authorization);
if (!$licencaBanco) {
    http_response_code(404);
    echo json_encode(["status" => false, "msg" => "Licença não encontrada no sistema."]);
    exit();
}

if ($licencaBanco->usado) {
    http_response_code(403);
    echo json_encode(["status" => false, "msg" => "Esta licença já foi utilizada no banco de dados."]);
    exit();
}

if ($licencaBanco->status !== "ativa") {
    http_response_code(403);
    echo json_encode(["status" => false, "msg" => "Licença inativa."]);
    exit();
}

if (time() > strtotime($licencaBanco->expira_em)) {
    http_response_code(403);
    echo json_encode(["status" => false, "msg" => "Licença expirada."]);
    exit();
}

// Criação da instituição
$instituicaoObj = new Instituicao();
$instituicaoObj->setNome($nome);
$instituicaoObj->setCEP($CEP);
$instituicaoObj->setLogradouro($logradouro);
$instituicaoObj->setCidade($cidade);
$instituicaoObj->setBairro($bairro);
$instituicaoObj->setCnpj($cnpj);
$instituicaoObj->setTipo($tipo);
$instituicaoObj->setTelefone($telefone);
$instituicaoObj->setEmail($email);
$instituicaoObj->setSite($site);
$instituicaoObj->setAtividade($atividade);
$instituicaoObj->setNomeResponsavel($nome_responsavel);
$instituicaoObj->setTelefoneResponsavel($telefone_responsavel);

$resultado = $instituicaoObj->cadastrar();

if ($resultado["status"]) {
    // Marca o token como usado e registra o id da instituição
    $meutoken->marcarUsado($authorization, $resultado["id_instituicao"]);

    http_response_code(201);
    echo json_encode([
        "status" => true,
        "msg" => $resultado["msg"],
        "id_instituicao" => $resultado["id_instituicao"],
        "login_adm" => $resultado["Login do ADM"],   
        "senha_adm" => $resultado["Senha do ADM"] 
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "status" => false,
        "msg" => $resultado["msg"]
    ]);
}
?>