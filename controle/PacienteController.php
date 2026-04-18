<?php

require_once __DIR__ . "/BaseController.php";
require_once __DIR__ . "/../modelo/PacienteModel.php";
require_once __DIR__ . "/../modelo/UsuarioModel.php";

class PacienteController extends BaseController {
    private $model;

    public function __construct() {
        $this->model = new PacienteModel();
    }

    public function list() {
        $usuario = $GLOBALS['usuario'];
        $dados = $this->model->getAll($usuario['instituicao_id']);
        $this->jsonResponse($dados);
    }

    public function show($id) {
        $usuario = $GLOBALS['usuario'];
        $dado = $this->model->getById($id, $usuario['instituicao_id']);
        if (!$dado) {
            $this->errorResponse("Paciente não encontrado", 404);
        }
        $this->jsonResponse($dado);
    }

    public function showByCpf($cpf) {
        $usuario = $GLOBALS['usuario'];
        $dado = $this->model->getByCpf($cpf, $usuario['instituicao_id']);
        if (!$dado) {
            $this->errorResponse("Paciente não encontrado", 404);
        }
        $this->jsonResponse($dado);
    }

    public function store() {
        $usuarioLogado = $GLOBALS['usuario'];

if (!$usuarioLogado || empty($usuarioLogado['instituicao_id'])) {
            $this->errorResponse("Usuário não autenticado ou sem instituição", 401);
        }

        $instituicaoId = $usuarioLogado['instituicao_id'];

        
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!$data) $this->errorResponse("Dados inválidos");

        $usuarioModel = new UsuarioModel();
        $this->model->setDb($usuarioModel->getDb());
        $usuarioModel->beginTransaction();

        try {
            if (empty($data['nome']) || empty($data['cpf'])) {
                throw new Exception("Nome e CPF são obrigatórios");
            }

            $cleanCpf = preg_replace('/[^0-9]/', '', $data['cpf']);

            // 1. Create the user account
            // If email is not provided, generate a fallback using CPF to satisfy DB uniqueness
            $email = !empty($data['email']) ? $data['email'] : $cleanCpf . '@paciente.med.br';
            $senha = !empty($data['senha']) ? $data['senha'] : substr($cleanCpf, 0, 6);

            $usuarioId = $usuarioModel->create([
                'email'        => $email,
                'senha_hash'   => password_hash($senha, PASSWORD_DEFAULT),
                'instituicao_id' => $instituicaoId,
                'nome'         => $data['nome'],
                'cpf'          => $cleanCpf,
            ]);

            // Ensure Role 'PACIENTE' exists
            $role = $usuarioModel->getRoleByName('PACIENTE', $instituicaoId);
            if (!$role) {
                $roleId = $usuarioModel->createRole('PACIENTE', 'Paciente cadastrado', $instituicaoId);
                $role = ['id' => $roleId];
                // Bind default permissions natively
                $usuarioModel->assignDefaultPacientePermissions($roleId);
            }

            // Assign role to the new user
            $usuarioModel->assignRole($usuarioId, $role['id'], $instituicaoId);

            // 2. Create the patient record in the `pacientes` table
            $pacienteData = [
                'instituicao_id'   => (int) $instituicaoId,
                'cpf'              => $cleanCpf,
                'nome'             => $data['nome'],
            ];

            if (!empty($data['sexo']))              $pacienteData['sexo'] = $data['sexo'];
            if (!empty($data['endereco']))          $pacienteData['endereco'] = $data['endereco'];
            if (!empty($data['telefone']))          $pacienteData['telefone'] = $data['telefone'];
            if (!empty($data['profissao']))         $pacienteData['profissao'] = $data['profissao'];
            if (!empty($data['estado_civil']))      $pacienteData['estado_civil'] = $data['estado_civil'];
            if (!empty($data['nome_cuidador']))     $pacienteData['nome_cuidador'] = $data['nome_cuidador'];
            if (!empty($data['telefone_cuidador'])) $pacienteData['telefone_cuidador'] = $data['telefone_cuidador'];


            if ($usuarioId) {
                $pacienteData['usuario_id'] = (int) $usuarioId;
            }

            $pacienteId = $this->model->create($pacienteData);

            $usuarioModel->commit();
            $cpfLog = $data['cpf'] ?? 'desconhecido';
            $this->registrarAuditoria('Criou Paciente', "Criou o paciente CPF: " . $cpfLog);
            $this->jsonResponse(["id" => $pacienteId, "msg" => "Paciente criado com sucesso"], true, 201);
        } catch (Exception $e) {
            $usuarioModel->rollback();
            $this->errorResponse($e->getMessage());
        }
    }

    public function update($id) {
        $usuarioLogado = $GLOBALS['usuario'];
        $instituicaoId = $usuarioLogado['instituicao_id'];
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!$data) $this->errorResponse("Dados inválidos");

        $usuarioModel = new UsuarioModel();
        $this->model->setDb($usuarioModel->getDb());
        $usuarioModel->beginTransaction();

        try {
            // Patient-only fields go to `pacientes` table
            $pacienteData = [];
            if (isset($data['nome']))              $pacienteData['nome'] = $data['nome'];
            if (isset($data['cpf']))               $pacienteData['cpf'] = preg_replace('/[^0-9]/', '', $data['cpf']);
            if (isset($data['sexo']))              $pacienteData['sexo'] = $data['sexo'];
            if (isset($data['endereco']))          $pacienteData['endereco'] = $data['endereco'];
            if (isset($data['telefone']))          $pacienteData['telefone'] = $data['telefone'];
            if (isset($data['profissao']))         $pacienteData['profissao'] = $data['profissao'];
            if (isset($data['estado_civil']))      $pacienteData['estado_civil'] = $data['estado_civil'];
            if (isset($data['nome_cuidador']))     $pacienteData['nome_cuidador'] = $data['nome_cuidador'];
            if (isset($data['telefone_cuidador'])) $pacienteData['telefone_cuidador'] = $data['telefone_cuidador'];

            if (!empty($pacienteData)) {
                $this->model->update($id, $instituicaoId, $pacienteData);
            }

            // User auth fields go to `usuarios` table
            $paciente = $this->model->getById($id, $instituicaoId);
            if ($paciente && $paciente['usuario_id']) {
                $usuarioData = [];
                if (!empty($data['email'])) $usuarioData['email'] = $data['email'];
                if (isset($data['cpf']))    $usuarioData['cpf'] = preg_replace('/[^0-9]/', '', $data['cpf']);
                if (isset($data['nome']))   $usuarioData['nome'] = $data['nome'];
                if (!empty($data['senha'])) $usuarioData['senha_hash'] = password_hash($data['senha'], PASSWORD_DEFAULT);
                
                if (!empty($usuarioData)) {
                    $usuarioModel->update($paciente['usuario_id'], $instituicaoId, $usuarioData);
                }
            }

            $usuarioModel->commit();
            $this->registrarAuditoria('Editou Paciente', "Editou o paciente ID: " . $id);
            $this->jsonResponse(["msg" => "Paciente atualizado com sucesso"]);
        } catch (Exception $e) {
            $usuarioModel->rollback();
            $this->errorResponse($e->getMessage());
        }
    }

    public function delete($id) {
        $usuarioLogado = $GLOBALS['usuario'];
        $instituicaoId = $usuarioLogado['instituicao_id'];
        
        $usuarioModel = new UsuarioModel();
        $this->model->setDb($usuarioModel->getDb());
        
        $paciente = $this->model->getById($id, $instituicaoId);
        
        $usuarioModel->beginTransaction();
        try {
            // 1. Soft delete fixed patient profile
            $this->model->delete($id, $instituicaoId);
            
            // 2. Soft delete associated user account if it exists
            if ($paciente && $paciente['usuario_id']) {
                $usuarioModel->query(
                    "UPDATE usuarios SET deleted_at = NOW(), ativo = 0 WHERE id = ? AND instituicao_id = ?",
                    [$paciente['usuario_id'], $instituicaoId],
                    "ii"
                );
            }
            
            $usuarioModel->commit();
            $this->registrarAuditoria('Deletou Paciente', "Excluiu o paciente ID: " . $id);
            $this->jsonResponse(["msg" => "Paciente e conta de usuário excluídos com sucesso"]);
        } catch (Exception $e) {
            $usuarioModel->rollback();
            $this->errorResponse($e->getMessage());
        }
    }
}
