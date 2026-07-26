<?php

require_once __DIR__ . "/BaseController.php";
require_once __DIR__ . "/../modelo/UsuarioModel.php";
require_once __DIR__ . "/../config/jwt.php";
use Firebase\JWT\JWT;

class AuthController extends BaseController {
    private $model;

    public function __construct() {
        $this->model = new UsuarioModel();
    }

    public function login() {
        $input = json_decode(file_get_contents("php://input"), true);

        if (!isset($input['email'], $input['senha'])) {
            $this->errorResponse("Email e senha são obrigatórios");
        }

        $usuario = $this->model->getAuthenticatedUser($input['email']);

        if (!$usuario || !password_verify($input['senha'], $usuario['senha_hash'])) {
            $this->errorResponse("Credenciais inválidas", 401);
        }

        if (!$usuario['licenca_ativa']) {
            $this->errorResponse("Licença inativa ou expirada", 403);
        }

        $isOwner = isset($usuario['admin_owner']) && $usuario['admin_owner'] == 1;

        $payload = [
            "iss" => "tcc25",
            "sub" => $usuario['id'],
            "email" => $usuario['email'],
            "nome" => $usuario['nome'],
            "instituicao_id" => $usuario['instituicao_id'],
            "licenca_ativa" => (bool) $usuario['licenca_ativa'],
            "tipo_licenca" => $usuario['tipo_licenca'],
            "is_owner" => $isOwner,
            "iat" => time(),
            "exp" => time() + (60 * 60)
        ];

        $accessToken = JWT::encode($payload, JWT_SECRET, 'HS256');

        $refreshTokenPlain = bin2hex(random_bytes(64));
        $refreshTokenHash  = password_hash($refreshTokenPlain, PASSWORD_DEFAULT);
        $expiraEm = date('Y-m-d H:i:s', strtotime('+30 days'));

        $this->model->saveRefreshToken($usuario['id'], $refreshTokenHash, $expiraEm);

        $GLOBALS['usuario'] = [
            'id' => $usuario['id'],
            'instituicao_id' => $usuario['instituicao_id']
        ];
        $this->registrarAuditoria('Login', "Usuário efetuou login no sistema.");

        $this->jsonResponse([
            "access_token"  => $accessToken,
            "refresh_token" => $refreshTokenPlain,
            "expires_in"    => 3600
        ]);
    }

    public function me() {
        if (!isset($GLOBALS['usuario'])) {
            $this->errorResponse("Não autenticado", 401);
        }
        $this->jsonResponse($GLOBALS['usuario']);
    }

    public function logout() {
        // In a stateless JWT setup, logout on server side usually involves blacklisting tokens if needed, 
        // or just revoking the refresh token.
        $this->jsonResponse(["msg" => "Logout realizado com sucesso"]);
    }
}
