<?php

require_once "modelo/Paciente/Paciente.php";
require_once "modelo/MeuTokenJWT2.php";
use Firebase\JWT\MeuTokenJWT;

require_once "modelo/Medico/Medico.php";
require_once "modelo/MeuTokenJWT.php";

$headers = getallheaders();
$metodo = $_SERVER['REQUEST_METHOD'];
$vetor = explode("/", $_SERVER['REQUEST_URI']);

if (!isset($headers['Authorization'])) {
    header("HTTP/1.1 401 Unauthorized");
    echo json_encode(["mensagem" => "Token não fornecido."]);
    exit();
}

$authorization = $headers['Authorization'];
$meutoken = new MeuTokenJWT();

if ($meutoken->validarToken($authorization) === true) {


    if ($metodo === "GET") {

        if (!isset($vetor[3]) || empty($vetor[3])) {
            header("HTTP/1.1 400 Bad Request");
            echo json_encode(["mensagem" => "Filtro (nome, email ou CPF) não fornecido na URL."]);
            exit();
        }

        $filtro = urldecode($vetor[3]);
        $paciente = new Paciente();
        $paciente->setinstituicao($meutoken->getPayload()->instituicao);
        
        if (is_numeric($filtro)) {
            $paciente->setCpf($filtro);
            $pacienteSelecionado = $paciente->readCPFdiagnostico();
        } else {
            $paciente->setNome($filtro);
            $pacienteSelecionado = $paciente->readStringdiagnostico(); 
        }

        if ($pacienteSelecionado) {

            $pacientesArray = [];

            if (is_array($pacienteSelecionado)) {
                foreach ($pacienteSelecionado as $p) {
                    $pacientesArray[] = [
                        "cpf" => $p->getCpf(),
                        "nome" => $p->getNome(),
                        "sexo" => $p->getSexo(),
                        "endereco" => $p->getEndereco(),
                        "telefone" => $p->getTelefone(),
                        "email" => $p->getEmail(),
                        "profissao" => $p->getProfissao(),
                        "estado_civil" => $p->getEstadoCivil(),
                        "nome_cuidador" => $p->getNomeCuidador(),
                        "telefone_cuidador" => $p->getTelefoneCuidador()
                    ];
                }
            }

            header("HTTP/1.1 200 OK");
            echo json_encode([
                "cod" => 200,
                "msg" => "Paciente(s) encontrado(s)",
                "pacientes" => $pacientesArray
            ]);
            
        } else {
            header("HTTP/1.1 404 Not Found");
            echo json_encode([
                "cod" => 404,
                "msg" => "Paciente não encontrado"
            ]);
        }

    } else {
        header("HTTP/1.1 405 Method Not Allowed");
        echo json_encode([
            "cod" => 405,
            "msg" => "Método não permitido. Use GET."
        ]);
    }

} else {
    header("HTTP/1.1 401 Unauthorized");
    echo json_encode(["mensagem" => "Token inválido."]);
}
?>
