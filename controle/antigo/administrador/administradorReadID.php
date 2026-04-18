<?php

require_once "modelo/adm/administrador.php";
require_once "modelo/MeuTokenJWT2.php";
use Firebase\JWT\MeuTokenJWT;
require_once "modelo/MeuTokenJWT.php";

$headers = getallheaders();
$metodo = $_SERVER['REQUEST_METHOD'];
$vetor = explode("/", $_SERVER['REQUEST_URI']);

if (!isset($headers['Authorization'])) {
    header("HTTP/1.1 401 Unauthorized");
    echo json_encode([["status" => false, "msg" => "Token não fornecido."]]);
    exit();
}

$authorization = $headers['Authorization'];
$meutoken = new MeuTokenJWT();

if ($meutoken->validarToken($authorization) === true) {

    if ($metodo === "GET") {

        if (!isset($vetor[3]) || empty($vetor[3])) {
            header("HTTP/1.1 400 Bad Request");
            echo json_encode([["status" => false, "msg" => "Filtro (nome, email ou CPF) não fornecido na URL."]]);
            exit();
        }

        $filtro = urldecode($vetor[3]);
        $adm = new Adm();
        $adm->setNome($filtro);

        $payloadRecuperado = $meutoken->getPayload();
        $instituicao = $payloadRecuperado->instituicao;

        $admSelecionado = $adm->readString($instituicao); 
        
        if ($admSelecionado) {
            $administradoresArray = [];

            if (is_array($admSelecionado)) {
                foreach ($admSelecionado as $p) {
                    $administradoresArray[] = [
                        "id" => $p->getId(),
                        "nome" => $p->getNome(),
                        "email" => $p->getEmail()
                    ];
                }
            } else {
                $p = $admSelecionado;
                $administradoresArray[] = [
                    "id" => $p->getId(),
                    "nome" => $p->getNome(),
                    "email" => $p->getEmail()
                ];
            }

            header("HTTP/1.1 200 OK");
            echo json_encode([[
                "status" => true,
                "msg" => "Dados encontrados",
                "administradores" => $administradoresArray
            ]]);
        } else {
            header("HTTP/1.1 404 Not Found");
            echo json_encode([[
                "status" => false,
                "msg" => "Administrador não encontrado",
                "administradores" => []
            ]]);
        }

    } else {
        header("HTTP/1.1 405 Method Not Allowed");
        echo json_encode([[
            "status" => false,
            "msg" => "Método não permitido. Use GET.",
            "administradores" => []
        ]]);
    }

} else {
    header("HTTP/1.1 401 Unauthorized");
    echo json_encode([[
        "status" => false,
        "msg" => "Token inválido.",
        "administradores" => []
    ]]);
}
?>
