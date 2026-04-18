<?php
namespace Firebase\JWT;
require_once __DIR__ . "/Banco.php";

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

require_once "jwt/JWT.php";
require_once "jwt/Key.php";
require_once "jwt/SignatureInvalidException.php";

require_once "jwt/ExpiredException.php";

class MeuTokenJWT2
{
    //chave de criptografia, defina uma chave forte e a mantenha segura.
    private $key = "x9S4q0v+V0IjvHkG20uAxaHx1ijj+q1HWjHKv+ohxp/oK+77qyXkVj/l4QYHHTF3a";

    //algoritmo de criptografia para assinatura
    //Suportados: 'HS256' , 'ES384','ES256', 'ES256K', ,'HS384', 'HS512', 'RS256', 'RS384'
    private $alg = 'HS256';
    private $type = 'JWT';
    private $iss = 'http://localhost'; //emissor do token
    private $aud = 'http://localhost'; //destinatário do token
    private $sub = "acesso_sistema";   //assunto do token
    private $iat = "";  //momento de emissão
    private $exp = "";  //momento de expiração
    private $nbf = ""; //não é válido antes do tempo especificado
    private $jti = "";  //Identificador único
    private $payload; //claims 

   // private $duracaoToken = 3600 * 24 * 30; //3600 segundos = 60 min

    public function registrarToken($token, $payload)
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        // Ajustado para incluir 'instituicao' e 'usado'
        $SQL = "INSERT INTO licencas (token, email, instituicao, tipo_licenca, status, usado, expira_em)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conexao->prepare($SQL);

        if (!$stmt) {
            throw new Exception("Erro ao preparar query: " . $conexao->error);
        }

        // Cria variáveis separadas para passar por referência
        $instituicao = null; // ainda não vinculada
        $usado = 0; // token ainda não usado
        $expira_em = date('Y-m-d H:i:s', $payload->exp);

        $stmt->bind_param(
            "ssissis",
            $token,
            $payload->email,
            $instituicao,
            $payload->tipo_licenca,
            $payload->status,
            $usado,
            $expira_em
        );

        return $stmt->execute();
    }


    public function marcarUsado($token, $id_instituicao)
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        $SQL = "UPDATE licencas SET usado = 1, instituicao = ? WHERE token = ?";
        $stmt = $conexao->prepare($SQL);
        $stmt->bind_param("is", $id_instituicao, $token);
        return $stmt->execute();
    }

    public function verificarToken($token)
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        $SQL = "SELECT * FROM licencas WHERE token = ?";
        $stmt = $conexao->prepare($SQL);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $resultado = $stmt->get_result();
        return $resultado->fetch_object();
    }


    public function gerarToken($parametro_claims)
    {

        // Criação dos headers como objeto da classe stdClass
        $objHeaders = new stdClass();
        $objHeaders->alg = $this->alg;
        $objHeaders->typ = $this->type;

        // Criação do payload como objeto da classe stdClass
        $objPayload = new stdClass();

        //Registered Claims
        $objPayload->iss = $this->iss;        // emissor do token
        $objPayload->aud = $this->aud;        // destinatário do token
        $objPayload->sub = $this->sub;        // assunto do token  
        $objPayload->iat = time();            // momento de criação do token
        $objPayload->exp = $parametro_claims->exp;   // momento de expiração = tempo atual + duração
        $objPayload->nbf = time();        // momento em que o token torna-se valido. 
        $objPayload->jti = bin2hex(random_bytes(16)); // gera um valor aleatório para jti;

        //Public Claims

        $objPayload->email = $parametro_claims->email;           // email do comprador
        $objPayload->tipo_licenca = $parametro_claims->tipo_licenca ?? "anual"; // tipo de licença
        $objPayload->status = "ativa";                           // status inicial
        $objPayload->chave_unica = uniqid("lic_", true);         // chave única da licença
        $objPayload->usado = false;                              // indica se já foi usada
        $objPayload->id_instituicao = null;                     // vai ser preenchido quando usado


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
