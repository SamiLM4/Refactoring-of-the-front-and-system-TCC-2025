<?php
require_once "modelo/Medico/Medico.php";

use Firebase\JWT\MeuTokenJWT;

require_once "modelo/Medico/Medico.php";
require_once "modelo/MeuTokenJWT.php";

$headers = getallheaders();
$autorization = $headers['Authorization'] ?? null;

$meutoken = new MeuTokenJWT();

if ($meutoken->validarToken($autorization)) {
    $payloadRecuperado = $meutoken->getPayload();

    if (!isset($payloadRecuperado->papel) || $payloadRecuperado->papel !== "adm") {
        http_response_code(403);
        echo json_encode([
            "cod" => 403,
            "msg" => "Acesso negado. Apenas administradores podem realizar esta operação."
        ]);
        exit();
    }

    $jsonRecebidoBodyRequest = file_get_contents('php://input');
    $obj = json_decode($jsonRecebidoBodyRequest);

    $camposObrigatorios = ['cpf', 'crm', 'email', 'senha', 'nome'];
    foreach ($camposObrigatorios as $campo) {
        if (!isset($obj->$campo)) {
            echo json_encode([
                "cod" => 400,
                "msg" => "Campo obrigatório ausente: $campo."
            ]);
            exit();
        }
    }

    // Atribuição e sanitização
    $cpfMedico = trim($obj->cpf);
    $crmMedico = trim($obj->crm);
    $emailMedico = filter_var(trim($obj->email), FILTER_SANITIZE_EMAIL);
    $senhaMedico = trim($obj->senha);
    $nomeMedico = trim($obj->nome);

    // Verificações de campos vazios
    if (empty($cpfMedico) || empty($crmMedico) || empty($emailMedico) || empty($senhaMedico) || empty($nomeMedico)) {
        echo json_encode([
            "cod" => 400,
            "msg" => "Todos os campos devem ser preenchidos."
        ]);
        exit();
    }
/*
    if (!validarCPF($cpfMedico)) {
        echo json_encode([
            "cod" => 400,
            "msg" => "CPF inválido."
        ]);
        exit();
    }

    // Validação do CRM (apenas números, tamanho entre 4 e 10 dígitos geralmente)
    if (!preg_match('/^\d{4,10}$/', $crmMedico)) {
        echo json_encode([
            "cod" => 400,
            "msg" => "CRM inválido. Deve conter apenas números entre 4 e 10 dígitos."
        ]);
        exit();
    }

    // Validação de email
    if (!filter_var($emailMedico, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            "cod" => 400,
            "msg" => "Email inválido."
        ]);
        exit();
    }

    // Validação de senha (mínimo 6 caracteres, com letras e números)
    if (strlen($senhaMedico) < 6 || !preg_match('/[A-Za-z]/', $senhaMedico) || !preg_match('/[0-9]/', $senhaMedico)) {
        echo json_encode([
            "cod" => 400,
            "msg" => "Senha fraca. Deve conter pelo menos 6 caracteres, incluindo letras e números."
        ]);
        exit();
    }

    // Validação do nome (sem números e mínimo 2 palavras)
    if (!preg_match('/^[A-Za-zÀ-ú\s]{3,}$/', $nomeMedico) || str_word_count($nomeMedico) < 2) {
        echo json_encode([
            "cod" => 400,
            "msg" => "Nome inválido. Deve conter pelo menos 2 palavras e apenas letras."
        ]);
        exit();
    }

    // Se passou por todas as validações
    echo json_encode([
        "cod" => 200,
        "msg" => "Dados validados com sucesso.",
        "dados" => [
            "cpf" => $cpfMedico,
            "crm" => $crmMedico,
            "email" => $emailMedico,
            "nome" => $nomeMedico
            // Nota: normalmente a senha não é devolvida
        ]
    ]);
*/


    // Sanitize input
//$nome = strip_tags($nome);

    $medico = new Medico();

    $medico->setCpf($cpfMedico);
    $medico->setCrm($crmMedico);
    $medico->setEmail($emailMedico);
    $medico->setSenha($senhaMedico);
    $medico->setNome($nomeMedico);
    $medico->setinstituicao($payloadRecuperado->instituicao);

    if ($medico->cadastrarMedico()) {
        echo json_encode([
            "cod" => 201,
            "msg" => "Cadastrado com sucesso!"
        ]);
    } else {
        echo json_encode([
            "cod" => 500,
            "msg" => "ERRO ao cadastrar o Medico"
        ]);
    }
}

    // Validação do CPF
    function validarCPF($cpf)
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d)
                return false;
        }
        return true;
    }

?>