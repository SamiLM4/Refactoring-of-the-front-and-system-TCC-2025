<?php

require_once __DIR__ . "/../modelo/InstituicaoModel.php";
require_once __DIR__ . "/../modelo/LicencaModel.php";
require_once __DIR__ . "/../modelo/UsuarioModel.php";

class InstituicaoController extends BaseController {
    private $model;
    private $licencaModel;
    private $usuarioModel;

    public function __construct() {
        $this->model = new InstituicaoModel();
        $this->licencaModel = new LicencaModel();
        $this->usuarioModel = new UsuarioModel();
    }

    public function show($id) {
        $dado = $this->model->getById($id);
        if (!$dado) {
            $this->errorResponse("Instituição não encontrada", 404);
        }
        $this->jsonResponse($dado);
    }

    public function store() {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (!$data) {
            $this->errorResponse("Dados inválidos");
        }

        $id = $this->model->create($data);
        $this->jsonResponse(["id" => $id, "msg" => "Instituição criada com sucesso"], true, 201);
    }

    public function update($id = null) {
        $usuario = $GLOBALS['usuario'];
        $id = $id ?? $usuario['instituicao_id']; // If no ID, update current user's institution
        
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (!$data) {
            $this->errorResponse("Dados inválidos");
        }

        $this->model->update($id, $data);
        $this->jsonResponse(["msg" => "Instituição atualizada com sucesso"]);
    }

    public function register() {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!$data) $this->errorResponse("Dados inválidos");

        // Transaction handling would be better here, but for simplicity in this demo:
        try {
            // 1. Validate Token
            $licenca = $this->licencaModel->getByToken($data['token']);
            if (!$licenca) throw new Exception("Token inválido");
            if ($licenca['usado']) throw new Exception("Token já utilizado");
            if ($licenca['status'] !== 'ativa') throw new Exception("Licença inativa");

            // 2. Validate Duplicates
            if ($this->model->getByCnpj($data['cnpj'])) throw new Exception("CNPJ já cadastrado");
            if ($this->usuarioModel->getAuthenticatedUser($data['email_admin'])) throw new Exception("Email já cadastrado");

            // 3. Create Institution
            $instituicaoId = $this->model->create([
                "nome" => $data['nome'],
                "cep" => $data['cep'],
                "logradouro" => $data['logradouro'],
                "cidade" => $data['cidade'],
                "bairro" => $data['bairro'],
                "cnpj" => $data['cnpj'],
                "tipo" => $data['tipo'],
                "telefone" => $data['telefone'],
                "email" => $data['email'],
                "nome_responsavel" => $data['nome_responsavel'],
                "telefone_responsavel" => $data['telefone_responsavel']
            ]);

            // 4. Create Admin User with profile and flag
            $usuarioId = $this->usuarioModel->create([
                "instituicao_id" => $instituicaoId,
                "email" => $data['email_admin'],
                "senha_hash" => password_hash($data['senha'], PASSWORD_DEFAULT),
                "nome" => $data['nome_responsavel'],
                "cpf" => $data['cpf_admin'] ?? null,
                "admin_owner" => 1
            ]);

            // 5. Create Role and Assign
            $papelId = $this->usuarioModel->createRole("ADMIN", "Administrador Principal", $instituicaoId);
            $this->usuarioModel->assignRole($usuarioId, $papelId, $instituicaoId);

            // 6. Use License
            $this->licencaModel->markAsUsed($licenca['id'], $instituicaoId);

            $this->jsonResponse(["msg" => "Registro concluído com sucesso", "instituicao_id" => $instituicaoId], true, 201);

        } catch (Exception $e) {
            $this->errorResponse($e->getMessage());
        }
    }
}
