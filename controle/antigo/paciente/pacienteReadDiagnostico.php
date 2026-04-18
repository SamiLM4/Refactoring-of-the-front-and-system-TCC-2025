<?php

require_once "modelo/Paciente/Paciente.php";
require_once "modelo/MeuTokenJWT2.php";
require_once "modelo/medico/relacaoMedicoPaciente.php";

use Firebase\JWT\MeuTokenJWT;
require_once "modelo/Medico/Medico.php";
require_once "modelo/MeuTokenJWT.php";

$headers = getallheaders();
$autorization = $headers['Authorization'] ?? '';

$meutoken = new MeuTokenJWT();

$vetor = explode("/", $_SERVER['REQUEST_URI']);
$pagina = isset($vetor[2]) ? (int)$vetor[2] : 1;

if ($meutoken->validarToken($autorization) == true) {
    $payloadRecuperado = $meutoken->getPayload();
    header("Content-Type: application/json");

    // Instancia Paciente para todos os casos
    $paciente = new Paciente();
    $paciente->setinstituicao($payloadRecuperado->instituicao);
    $dados = $paciente->readDiagnosticos($pagina);

    if ($dados && $dados["status"] === true) {

        // 🔹 Se for médico, filtra apenas pacientes vinculados a ele
        if ($payloadRecuperado->papel == 'medico') {
            $relacao = new Relacao();
            $relacao->setcpfmedico($payloadRecuperado->cpf_medico);

            // Busca as relações do médico (já retorna cpf dos pacientes)
            $relacoes = $relacao->readCPFmedico($pagina);

            $cpfsPermitidos = [];
            if ($relacoes && isset($relacoes['pacientes'])) {
                foreach ($relacoes['pacientes'] as $pac) {
                    $cpfsPermitidos[] = $pac['cpf'];
                }
            }

            // Filtra os pacientes do retorno original
            $dados["pacientes"] = array_filter($dados["pacientes"], function($p) use ($cpfsPermitidos) {
                return in_array($p["cpf"], $cpfsPermitidos);
            });

            // Atualiza o total após o filtro
            $dados["total"] = count($dados["pacientes"]);
        }

        // 🔹 Retorno final (mesmo formato)
        if (!empty($dados["pacientes"])) {
            header("HTTP/1.1 200 OK");
            echo json_encode([
                "cod" => 200,
                "msg" => $dados["msg"],
                "pacientes" => array_values($dados["pacientes"]), // reindexa
                "total" => $dados["total"]
            ]);
        } else {
            header("HTTP/1.1 204 No Content");
            echo json_encode([
                "cod" => 204,
                "msg" => "Nenhum paciente encontrado.",
                "pacientes" => [],
                "total" => 0
            ]);
        }

    } else {
        header("HTTP/1.1 204 No Content");
        echo json_encode([
            "cod" => 204,
            "msg" => "Nenhum paciente encontrado.",
            "pacientes" => [],
            "total" => 0
        ]);
    }

} else {
    header("HTTP/1.1 401 Unauthorized");
    echo json_encode(["mensagem" => "Token inválido!"]);
}

?>
