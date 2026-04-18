<?php
use Firebase\JWT\MeuTokenJWT;
require_once "modelo/Medico/Medico.php";
require_once "modelo/MeuTokenJWT.php";

$headers = getallheaders();
$autorization = $headers['Authorization'];
$meutoken = new MeuTokenJWT();

$vetor = explode("/", $_SERVER['REQUEST_URI']);
$filtro = urldecode($vetor[3]);

if ($meutoken->validarToken($autorization) == true) {
    $payloadRecuperado = $meutoken->getPayload();

    $medico = new Medico();
    $medico->setInstituicao($payloadRecuperado->id_instituicao);

    if (is_numeric($filtro)) {
        $medico->setCpf($filtro);
        $medicoSelecionado = $medico->readCPFSemDiagnostico();
    } else {
        $medico->setNome($filtro);
        $medicoSelecionado = $medico->readStringSemDiagnostico();
    }

    if ($medicoSelecionado) {
        $medicosArray = [];

        if (is_array($medicoSelecionado)) {
            foreach ($medicoSelecionado as $p) {
                $medicosArray[] = [
                    "cpf" => $p->getCpf(),
                    "crm" => $p->getCrm(),
                    "email" => $p->getEmail(),
                    "nome" => $p->getNome()
                ];
            }
        } 

        header("Content-Type: application/json");
        http_response_code(200);
        echo json_encode([
            [
                "status" => true,
                "msg" => "Dados encontrados",
                "medicos" => $medicosArray
            ]
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            [
                "status" => false,
                "msg" => "Médico não encontrado",
                "medicos" => []
            ]
        ]);
    }
} else {
    http_response_code(401);
    echo json_encode([
        [
            "status" => false,
            "msg" => "Token inválido ou ausente",
            "medicos" => [],
        ]
    ]);
}
?>
