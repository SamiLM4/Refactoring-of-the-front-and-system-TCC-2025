<?php
require_once "modelo/adm/administrador.php";

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\MeuTokenJWT;

require_once "modelo/MeuTokenJWT.php";


$dados = json_decode(file_get_contents("php://input"));

$adm = new Adm();
$adm->setemail($dados->email);
$adm->setsenha($dados->senha);

if ($adm->login()) {

    $tokenJWT = new MeuTokenJWT();

    $objectClaimsToken = new stdClass();
    $objectClaimsToken->email = $adm->getemail();
    $objectClaimsToken->instituicao = $adm->getinstituicao();
    $objectClaimsToken->papel = $adm->getPapel();

    $novoToken = $tokenJWT->gerarToken($objectClaimsToken);


    echo json_encode([
        "cod" => 200,
        "msg" => "Login realizado com sucesso!!!",
        "adm" => [
            "id" => $adm->getid(),
            "instituicao" => $adm->getinstituicao(),
            "nome" => $adm->getnome(),
            "email" => $adm->getemail()
        ],
        "token" => $novoToken
    ]);

} else {
    echo json_encode(["status" => false, "msg" => "E-mail ou senha inválidos"]);
}
?>