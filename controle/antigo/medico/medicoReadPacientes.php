<?php
use Firebase\JWT\MeuTokenJWT;
require_once "modelo/Medico/Medico.php";
require_once "modelo/MeuTokenJWT.php";
require_once "modelo/medico/RelacaoMedicoPaciente.php";

$vetor = explode("/", $_SERVER['REQUEST_URI']);
$metodo = $_SERVER['REQUEST_METHOD'];

$headers = getallheaders();
$autorization = $headers['Authorization'];
$meutoken = new MeuTokenJWT();

$pagina = $vetor[3] ?? 1;

if ($meutoken->validarToken($autorization) == true) {
    $payloadRecuperado = $meutoken->getPayload();

    if ($metodo == "GET") {

        if ($payloadRecuperado->papel == "adm") {
            header("HTTP/1.1 403 Forbidden");
            echo json_encode([
                "cod" => 403,
                "msg" => "Acesso negado, apenas para médicos"
            ]);
            exit();
        }
        
        $cpf = $payloadRecuperado->cpf_medico;

        $medico = new Medico();
        $medico->setCpf($cpf);
        $medico->setinstituicao($payloadRecuperado->instituicao);
        $medicoSelecionado = $medico->readCPF();

        if ($medicoSelecionado && count($medicoSelecionado) > 0) {
            $primeiroMedico = $medicoSelecionado[0];

            $relacionamento = new Relacao();
            $relacionamento->setCpfMedico($primeiroMedico->getCpf());

            header("HTTP/1.1 200 OK");
            echo json_encode([
                "cod" => 200,
                "msg" => "Medico encontrado",
                "Médico" => [
                    "CPF" => $primeiroMedico->getCpf(),
                    "CRM" => $primeiroMedico->getCrm(),
                    "email" => $primeiroMedico->getEmail(),
                    "nome" => $primeiroMedico->getNome(),
                    "Pacientes" => $relacionamento->readCPFmedico($pagina)
                ],
            ]);
        } else {
            header("HTTP/1.1 404 Not Found");
            echo json_encode([
                "cod" => 404,
                "msg" => "Medico não encontrado"
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