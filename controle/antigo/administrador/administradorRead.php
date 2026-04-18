<?php
require_once "modelo/adm/administrador.php";

$vetor = explode("/", $_SERVER['REQUEST_URI']);
$pagina = $vetor[2];

use Firebase\JWT\MeuTokenJWT;

require_once "modelo/MeuTokenJWT.php";

$headers = getallheaders();
$autorization = $headers['Authorization'];
$meutoken = new MeuTokenJWT();

if ($meutoken->validarToken(stringToken: $autorization) == true) {
    $payloadRecuperado = $meutoken->getPayload();

    if (!isset($payloadRecuperado->papel) || $payloadRecuperado->papel !== "adm") {
        http_response_code(403);
        echo json_encode([
            "cod" => 403,
            "msg" => "Acesso negado. Apenas administradores podem realizar esta operação."
        ]);
        exit();
    }

    $instituicao = $payloadRecuperado->instituicao;
    $adm = new Adm();
    $dados = $adm->read($pagina, $instituicao);

    if ($dados !== false) {
        echo json_encode([$dados]);
    } else {
        echo json_encode(["status" => false, "msg" => "Erro ao buscar administradores"]);
    }
}
?>