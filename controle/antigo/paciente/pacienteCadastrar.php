<?php
require_once "modelo/Paciente/Paciente.php";

$jsonRecebidoBodyRequest = file_get_contents('php://input');
$obj = json_decode($jsonRecebidoBodyRequest);

use Firebase\JWT\MeuTokenJWT;
require_once "modelo/Medico/Medico.php";
require_once "modelo/MeuTokenJWT.php";
$headers = getallheaders();
$autorization = $headers['Authorization'];
$meutoken = new MeuTokenJWT();

if (!isset($headers['Authorization'])) {
    respostaErro(401, 10, "Cabeçalho 'Authorization' ausente.");
}


if ($meutoken->validarToken($autorization) == true) {
    $payloadRecuperado = $meutoken->getPayload();
/*
    if (!isset($payloadRecuperado->papel) || $payloadRecuperado->papel !== "medico") {
        http_response_code(403);
        echo json_encode([
            "cod" => 403,
            "msg" => "Acesso negado. Apenas Médicos podem realizar esta operação."
        ]);
        exit();
    }
*/
    if (!$obj) {
        respostaErro(400, 0, "JSON inválido ou não fornecido.");
    }

    $camposObrigatorios = ['cpf', 'nome', 'telefone', 'email'];
    foreach ($camposObrigatorios as $campo) {
        if (empty($obj->$campo)) {
            respostaErro(400, 1, "Campo obrigatório '$campo' não foi fornecido ou está vazio.");
        }
    }

    $cpf = strip_tags(trim($obj->cpf));
    $nome = strip_tags(trim($obj->nome));
    $sexo = isset($obj->sexo) ? strip_tags(trim($obj->sexo)) : null;
    $endereco = isset($obj->endereco) ? strip_tags(trim($obj->endereco)) : null;
    $telefone = strip_tags(trim($obj->telefone));
    $email = strip_tags(trim($obj->email));
    $profissao = isset($obj->profissao) ? strip_tags(trim($obj->profissao)) : null;
    $estado_civil = isset($obj->estado_civil) ? strip_tags(trim($obj->estado_civil)) : null;
    $nome_cuidador = isset($obj->nome_cuidador) ? strip_tags(trim($obj->nome_cuidador)) : null;
    $telefone_cuidador = isset($obj->telefone_cuidador) ? strip_tags(trim($obj->telefone_cuidador)) : null;

    if (!preg_match('/^\d{11}$/', $cpf)) {
        respostaErro(400, 2, "CPF inválido. Deve conter exatamente 11 dígitos numéricos.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respostaErro(400, 3, "Email inválido.");
    }
    if (!preg_match('/^\d{8,15}$/', $telefone)) {
        respostaErro(400, 4, "Telefone inválido. Deve conter entre 8 e 15 dígitos numéricos.");
    }

    if ($telefone_cuidador !== null && $telefone_cuidador !== '') {
        if (!preg_match('/^\d{8,15}$/', $telefone_cuidador)) {
            respostaErro(400, 5, "Telefone do cuidador inválido. Deve conter entre 8 e 15 dígitos numéricos.");
        }
    }

    if ($sexo !== null && !in_array($sexo, ['M', 'F', 'O'])) {
        respostaErro(400, 6, "Sexo inválido. Aceito apenas 'M', 'F' ou 'O'.");
    }

    // A partir daqui, já está tudo validado e sanitizado
    $Paciente = new Paciente();
    $Paciente->setCpf($cpf);
    $Paciente->setNome($nome);
    $Paciente->setSexo($sexo);
    $Paciente->setEndereco($endereco);
    $Paciente->setTelefone($telefone);
    $Paciente->setEmail($email);
    $Paciente->setProfissao($profissao);
    $Paciente->setEstadoCivil($estado_civil);
    $Paciente->setNomeCuidador($nome_cuidador);
    $Paciente->setTelefoneCuidador($telefone_cuidador);

    $Paciente->setinstituicao($payloadRecuperado->instituicao);

    if ($Paciente->cadastrarPaciente()) {
        http_response_code(201); // 201 Created
        echo json_encode([
            "cod" => 201,
            "msg" => "Paciente cadastrado com sucesso."
            // Idealmente não retornar objeto completo, pode retornar ID ou algo assim
        ]);
    } else {
        respostaErro(500, 6, "Erro interno ao cadastrar o paciente.");
    }
} else {
    header("HTTP/1.1 401 Unauthorized");
    echo json_encode(["mensagem" => "Token inválido."]);
}

// Função para enviar resposta de erro e terminar a execução
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