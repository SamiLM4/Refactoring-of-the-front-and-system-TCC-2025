<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\MeuTokenJWT;

require_once "modelo/Medico/Medico.php";
require_once "modelo/MeuTokenJWT.php";

$jsonRecebidoBodyRequest = file_get_contents('php://input');
$obj = json_decode($jsonRecebidoBodyRequest);

if (!isset($obj->email) || !isset($obj->senha)) {
    echo json_encode([
        "cod" => 400,
        "msg" => "Dados incorretos ou incompletos. Por favor, forneça email e senha válidos."
    ]);
    exit();
}

$emailMedico = $obj->email;
$senhaMedico = $obj->senha;

$medico = new Medico();
$medico->setEmail($emailMedico);
$medico->setSenha($senhaMedico);

//die;

if ($medico->login()) {
    $tokenJWT = new MeuTokenJWT();

    $objectClaimsToken = new stdClass();
    $objectClaimsToken->email = $medico->getEmail();   // universal
    $objectClaimsToken->instituicao = $medico->getinstituicao();   // universal
    $objectClaimsToken->papel = $medico->getPapel();   // universal
    $objectClaimsToken->cpf_medico = $medico->getcpf();   // universal


    $novoToken = $tokenJWT->gerarToken($objectClaimsToken);
    
    echo json_encode([
        "cod" => 200,
        "msg" => "Login realizado com sucesso!!!",
        "Medico" => [
            "cpf" => $medico->getCpf(),
            "instituicao" => $medico->getinstituicao(),
            "crm" => $medico->getCrm(),
            "email" => $medico->getEmail(),
            "nome" => $medico->getNome()
        ],
        "token" => $novoToken
    ]);
} else {
    echo json_encode([
        "cod" => 401,
        "msg" => "ERRO: Login inválido. Verifique suas credenciais."
    ]);
}
?>
