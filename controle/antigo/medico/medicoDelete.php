<?php
use Firebase\JWT\MeuTokenJWT;

require_once "modelo/Medico/Medico.php";
require_once "modelo/MeuTokenJWT.php";

$headers = getallheaders();
$autorization = $headers['Authorization'] ?? null;

$meutoken = new MeuTokenJWT();

if ($meutoken->validarToken($autorization)) {
    $payloadRecuperado = $meutoken->getPayload();
    $metodo = $_SERVER['REQUEST_METHOD'];

    if (!isset($payloadRecuperado->papel) || $payloadRecuperado->papel !== "adm") {
        http_response_code(403);
        echo json_encode([
            "cod" => 403,
            "msg" => "Acesso negado. Apenas administradores podem realizar esta operação."
        ]);
        exit();
    }

    if ($metodo === "DELETE") {
        $vetor = explode("/", $_SERVER['REQUEST_URI']);
        $cpf = $vetor[2] ?? null;

        // Validação: CPF informado?
        if (!$cpf || trim($cpf) === "") {
            http_response_code(400);
            echo json_encode([
                "cod" => 400,
                "msg" => "CPF não informado."
            ]);
            exit;
        }

        $cpf = trim($cpf);

        // Validação: CPF válido (somente números e 11 dígitos)
        if (!preg_match('/^\d{11}$/', $cpf)) {
            http_response_code(400);
            echo json_encode([
                "cod" => 400,
                "msg" => "Formato de CPF inválido. Deve conter 11 dígitos numéricos."
            ]);
            exit;
        }

        $medico = new Medico();
        $medico->setCpf($cpf);
        $medico->setinstituicao($payloadRecuperado->instituicao);

        // Verifica se o médico existe antes de tentar deletar
        $medicoExistente = $medico->readCPF();
        if (!$medicoExistente) {
            http_response_code(404);
            echo json_encode([
                "cod" => 404,
                "msg" => "Médico com o CPF informado não encontrado."
            ]);
            exit;
        }

        if ($medico->delete()) {
            http_response_code(200);
            echo json_encode([
                "cod" => 200,
                "msg" => "Médico excluído com sucesso."
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "cod" => 500,
                "msg" => "Erro ao excluir o médico."
            ]);
        }
    }
} else {
    http_response_code(401);
    echo json_encode(["mensagem" => "Token inválido ou ausente."]);
}
