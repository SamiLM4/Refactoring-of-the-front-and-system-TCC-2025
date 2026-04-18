<?php
namespace Firebase\JWT;

use Banco;
use stdClass;
use Firebase\JWT\Key;
use Firebase\JWT\JWT;
use DomainException;
use Exception;
use InvalidArgumentException;
use UnexpectedValueException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\ExpiredException;

require_once "modelo/MeuTokenJWT2.php";
use Firebase\JWT\MeuTokenJWT2;

require_once "jwt/JWT.php";
require_once "jwt/Key.php";
require_once "jwt/SignatureInvalidException.php";

require_once "jwt/ExpiredException.php";

require_once __DIR__ . "/Banco.php";

class MeuTokenJWT
{
    //chave de criptografia, defina uma chave forte e a mantenha segura.
    private $key = "x9S4q0v+V0IjvHkG20uAxaHx1ijj+q1HWjHKv+ohxp/oK+77qyXkVj/l4QYHHTF3";

    //algoritmo de criptografia para assinatura
    //Suportados: 'HS256' , 'ES384','ES256', 'ES256K', ,'HS384', 'HS512', 'RS256', 'RS384'
    private $alg = 'HS256';
    private $type = 'JWT';
    private $iss = 'http://localhost'; //emissor do token
    private $aud = 'http://localhost'; //destinatário do token
    private $sub = "acesso_sistema";   //assunto do token
    private $iat = "";  //momento de emissão
    private $exp = "";     //momento de expiração
    private $nbf = ""; //não é válido antes do tempo especificado
    private $jti = "";  //Identificador único
    private $payload; //claims 
    //tempo de validade do token
    private $duracaoToken = 3600 * 24 * 30; //3600 segundos = 60 min -> 3600 * 24 * 30

    public function gerarToken($parametro_claims)
    {
        $objHeaders = new stdClass();
        $objHeaders->alg = $this->alg;
        $objHeaders->typ = $this->type;

        $objPayload = new stdClass();
        $objPayload->iss = $this->iss;
        $objPayload->aud = $this->aud;
        $objPayload->sub = $this->sub;
        $objPayload->iat = time();
        //$objPayload->exp = time() + $this->duracaoToken;
        $objPayload->nbf = time();
        $objPayload->jti = bin2hex(random_bytes(16));

        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        $SQL = "Select expira_em FROM licencas WHERE instituicao = ?";
        $stmt = $conexao->prepare($SQL);
        $stmt->bind_param("s", $parametro_claims->instituicao);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $linha = $resultado->fetch_object();
        if ($linha && isset($linha->expira_em)) {
            $objPayload->exp = strtotime($linha->expira_em);
            if ($objPayload->exp <= time()) {
                throw new Exception("A licença da instituição já expirou.");
            }
        } else {
            throw new Exception("Não foi encontrado expira_em para a instituição informada.");
        }


        // ✅ Tornar universal
        if (isset($parametro_claims->email)) {
            $objPayload->email = $parametro_claims->email;
        }
        /*
        if (isset($parametro_claims->senha)) {
            $objPayload->senha = $parametro_claims->senha;
        }
        */
        if (isset($parametro_claims->papel)) {
            $objPayload->papel = $parametro_claims->papel;
        }
        if (isset($parametro_claims->cpf_medico)) {
            $objPayload->cpf_medico = $parametro_claims->cpf_medico;
        }
        if (isset($parametro_claims->instituicao)) {
            $objPayload->instituicao = $parametro_claims->instituicao;
        }

        $token = JWT::encode((array) $objPayload, $this->key, $this->alg, null, (array) $objHeaders);
        return $token;
    }


    public function validarToken($stringToken)
    {
        if (isset($stringToken)) {
            if ($stringToken == "") {
                return false;
            } else {
                $remover = ["Bearer ", " "];
                $token = str_replace($remover, "", $stringToken);
                try {

                    $payloadValido = JWT::decode($token, new Key($this->key, $this->alg));
                    $this->setPayload($payloadValido);

                    return true;
                } catch (SignatureInvalidException $e) {
                    // A assinatura do token é inválida.
                    //error_log("Invalid token signature: " . $e->getMessage());

                    return false;
                } catch (BeforeValidException $e) {
                    // O token não é válido ainda (antes do tempo 'nbf').
                    //error_log("Token not valid yet: " . $e->getMessage());

                    return false;
                } catch (ExpiredException $e) {
                    // O token expirou.
                    // error_log("Token expired: " . $e->getMessage());

                    return false;
                } catch (InvalidArgumentException $e) {
                    // Argumento inválido passado.
                    //error_log("Invalid argument: " . $e->getMessage());
                    return false;
                } catch (DomainException $e) {
                    // Exceção de domínio genérica.
                    //error_log("Domain exception: " . $e->getMessage());
                    return false;
                } catch (UnexpectedValueException $e) {
                    // Valor inesperado encontrado.
                    //error_log("Unexpected value: " . $e->getMessage());
                    return false;
                } catch (Exception $e) {
                    // Qualquer outra exceção genérica.
                    //error_log("General exception: " . $e->getMessage());
                    return false;
                }
            }
        }
        return false;
    }

    /**
     * Get the value of payload
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * Set the value of payload
     *
     * @return  self
     */
    public function setPayload($payload)
    {
        $this->payload = $payload;

        return $this;
    }

    /**
     * Get the value of alg
     */
    public function getAlg()
    {
        return $this->alg;
    }

    /**
     * Set the value of alg
     *
     * @return  self
     */
    public function setAlg($alg)
    {
        $this->alg = $alg;

        return $this;
    }
}
