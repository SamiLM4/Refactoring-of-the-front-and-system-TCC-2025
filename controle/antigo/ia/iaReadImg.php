<?php
// controle/ia/iaReadImg.php
// Obs: este arquivo é incluído pelo roteador, que passa $cpf como parâmetro.

require_once(__DIR__ . "/../../modelo/ia/ia.php");

header("Content-Type: application/json; charset=UTF-8");

// evitar qualquer saída antes do json (sem echo, print_r, var_dump)
if (!isset($cpf)) {
    http_response_code(400);
    echo json_encode(["cod" => 400, "msg" => "CPF não informado na rota"]);
    exit;
}

try {
    $ia = new IAResultado();
    $ia->setCpf($cpf);
    $resultado = $ia->readCPF();

    if (!$resultado) {
        http_response_code(404);
        echo json_encode(["cod" => 404, "msg" => "Nenhum diagnóstico encontrado para este CPF"]);
        exit;
    }

    // Recupera imagens — o modelo pode devolver array ou string JSON
    $imagens = $resultado->getImagens();

    if (is_string($imagens)) {
        $decoded = json_decode($imagens, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $imagens = $decoded;
        } else {
            // caso seja apenas uma string base64 (registro antigo), transforma em array
            $imagens = [$imagens];
        }
    }

    if (!is_array($imagens)) {
        $imagens = [];
    }

    echo json_encode([
        "cod" => 200,
        "cpf" => $resultado->getCpf(),
        "nome" => $resultado->getNome(),
        "imagens" => $imagens,
        "diagnostico" => $resultado->getDiagnostico(),
        "data" => $resultado->getData()
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    // em produção não exponha stack trace; aqui enviamos a mensagem para facilitar debug
    echo json_encode(["cod" => 500, "msg" => "Erro interno ao recuperar imagens", "error" => $e->getMessage()]);
}
