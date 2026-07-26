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

if ($meutoken->validarToken($autorization) == true) {
    $payloadRecuperado = $meutoken->getPayload();

    if ($metodo == "GET") {

        $nome = $vetor[3];
        $nome = urldecode($nome);
        $pagina = $vetor[4] ?? 1;

        $medico = new Medico();
        $medico->setnome($nome);
        $medico->setinstituicao($payloadRecuperado->instituicao);
        $medicoSelecionado = $medico->readNome();

        if ($medicoSelecionado) {
            $relacionamento = new Relacao();
            $relacionamento->setCpfMedico($medicoSelecionado->getCpf());

            header("HTTP/1.1 200 OK");
            echo json_encode([
                "cod" => 200,
                "msg" => "Medico encontrado",
                "Médico" => [
                    "CPF" => $medicoSelecionado->getCpf(),
                    "CRM" => $medicoSelecionado->getCrm(),
                    "email" => $medicoSelecionado->getEmail(),
                    "nome" => $medicoSelecionado->getNome(),
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