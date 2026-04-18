<?php
use Firebase\JWT\MeuTokenJWT;
require_once "modelo/Medico/Medico.php";
require_once "modelo/MeuTokenJWT.php";
$vetor = explode("/", $_SERVER['REQUEST_URI']);
$metodo = $_SERVER['REQUEST_METHOD'];

$headers = getallheaders();
$autorization = $headers['Authorization'];
$meutoken = new MeuTokenJWT();

if ($meutoken->validarToken($autorization) == true) {
    $payloadRecuperado = $meutoken->getPayload();

    if ($metodo === "GET") {
        // Verifica se o CRM foi passado na URL
        if (!isset($vetor[3]) || empty(trim($vetor[3]))) {
            header("HTTP/1.1 400 Bad Request");
            echo json_encode([
                "cod" => 400,
                "msg" => "CRM não informado"
            ]);
            exit;
        }

        $crm = trim($vetor[3]);

        // Valida se o CRM tem um formato mínimo aceitável (ex: numérico ou alfanumérico com até 20 caracteres)
        if (!preg_match("/^[a-zA-Z0-9]{1,20}$/", $crm)) {
            header("HTTP/1.1 400 Bad Request");
            echo json_encode([
                "cod" => 400,
                "msg" => "Formato de CRM inválido"
            ]);
            exit;
        }

        $medico = new Medico();
        $medico->setCrm($crm);
        $medico->setinstituicao($payloadRecuperado->instituicao);
        $medicoSelecionado = $medico->readCRM();

        // Verifica se houve erro na consulta (false) ou se não encontrou nada (null)
        if ($medicoSelecionado === false) {
            header("HTTP/1.1 500 Internal Server Error");
            echo json_encode([
                "cod" => 500,
                "msg" => "Erro ao buscar o médico no banco de dados"
            ]);
        } elseif ($medicoSelecionado === null || count($medicoSelecionado) === 0) {
            header("HTTP/1.1 404 Not Found");
            echo json_encode([
                "cod" => 404,
                "msg" => "Médico não encontrado"
            ]);
        } else {
            $dadosMedicos = [];

            foreach ($medicoSelecionado as $m) {
                $dadosMedicos[] = [
                    "CPF" => $m->getCpf(),
                    "CRM" => $m->getCrm(),
                    "email" => $m->getEmail(),
                    "nome" => $m->getNome()
                ];
            }

            header("HTTP/1.1 200 OK");
            echo json_encode([
                "cod" => 200,
                "msg" => "Médicos encontrados",
                "medicos" => $dadosMedicos
            ]);
        }
    } else {
        header("HTTP/1.1 405 Method Not Allowed");
        echo json_encode([
            "cod" => 405,
            "msg" => "Método não permitido"
        ]);
    }



} else {

    header("HTTP/1.1 404 Not Found");
    echo json_encode(["mensagem" => "Erro"]);

}
?>