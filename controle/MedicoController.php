<?php

require_once __DIR__ . "/BaseController.php";
require_once __DIR__ . "/../modelo/MedicoModel.php";
require_once __DIR__ . "/../modelo/UsuarioModel.php";

class MedicoController extends BaseController {
    private $model;

    public function __construct() {
        $this->model = new MedicoModel();
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
            $this->errorResponse("Médico não encontrado", 404);
        }
        $this->jsonResponse($dado);
    }

    public function showByCrm($crm) {
        $usuario = $GLOBALS['usuario'];
        $dado = $this->model->getByCrm($crm, $usuario['instituicao_id']);
        if (!$dado) {
            $this->errorResponse("Médico não encontrado", 404);
        }
        $this->jsonResponse($dado);
    }

    public function store() {
        $usuarioLogado = $GLOBALS['usuario'];
        $instituicaoId = $usuarioLogado['instituicao_id'];
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (!$data) $this->errorResponse("Dados inválidos");

        $usuarioModel = new UsuarioModel();
        $usuarioModel->beginTransaction();

        try {
            // 1. Create User directly with profile fields
            if (empty($data['email'])) throw new Exception("Email é obrigatório");
            if (empty($data['nome']) || empty($data['cpf']) || empty($data['crm'])) {
                throw new Exception("Nome, CPF e CRM são obrigatórios");
            }

            $senha = $data['senha'] ?? '123456';
            $usuarioId = $usuarioModel->create([
                'email' => $data['email'],
                'senha_hash' => password_hash($senha, PASSWORD_DEFAULT),
                'instituicao_id' => $instituicaoId,
                'nome' => $data['nome'],
                'cpf' => $data['cpf'],
                'crm' => $data['crm'],
                'especialidade' => $data['especialidade'] ?? null
            ]);

            // 2. Ensure Role 'MEDICO'
            $role = $usuarioModel->getRoleByName('MEDICO', $instituicaoId);
            if (!$role) {
                $roleId = $usuarioModel->createRole('MEDICO', 'Médico responsável', $instituicaoId);
                $role = ['id' => $roleId];
            }
            
            // 3. Assign role
            $usuarioModel->assignRole($usuarioId, $role['id'], $instituicaoId);

            $usuarioModel->commit();
            $this->jsonResponse(["id" => $usuarioId, "msg" => "Médico criado com sucesso"], true, 201);
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

        try {
            $updateData = [];
            if (!empty($data['email'])) $updateData['email'] = $data['email'];
            if (!empty($data['senha'])) $updateData['senha_hash'] = password_hash($data['senha'], PASSWORD_DEFAULT);
            if (isset($data['nome'])) $updateData['nome'] = $data['nome'];
            if (isset($data['cpf'])) $updateData['cpf'] = $data['cpf'];
            if (isset($data['crm'])) $updateData['crm'] = $data['crm'];
            if (isset($data['especialidade'])) $updateData['especialidade'] = $data['especialidade'];

            if (empty($updateData)) throw new Exception("Nenhum dado para atualizar");

            $usuarioModel->update($id, $instituicaoId, $updateData);
            $this->jsonResponse(["msg" => "Médico atualizado com sucesso"]);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage());
        }
    }

    public function delete($id) {
        $usuario = $GLOBALS['usuario'];
        $this->model->delete($id, $usuario['instituicao_id']);
        $this->jsonResponse(["msg" => "Médico excluído com sucesso"]);
    }

    public function listPacientes($medicoId) {
        $usuario = $GLOBALS['usuario'];
        $sql = "SELECT p.* FROM pacientes p JOIN medico_paciente mp ON p.id = mp.paciente_id WHERE mp.medico_id = ? AND p.instituicao_id = ?";
        $result = $this->model->query($sql, [$medicoId, $usuario['instituicao_id']], "ii");
        $dados = [];
        while ($row = $result->fetch_assoc()) $dados[] = $row;
        $this->jsonResponse($dados);
    }

    public function attachPaciente($medicoId) {
        $usuario = $GLOBALS['usuario'];
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        if (!isset($data['paciente_id'])) $this->errorResponse("ID do paciente é obrigatório");
        
        $sql = "INSERT INTO medico_paciente (medico_id, paciente_id) VALUES (?, ?)";
        $this->model->query($sql, [$medicoId, $data['paciente_id']], "ii");
        $this->jsonResponse(["msg" => "Paciente vinculado com sucesso"]);
    }

    public function detachPaciente($medicoId, $pacienteId) {
        $usuario = $GLOBALS['usuario'];
        $sql = "DELETE FROM medico_paciente WHERE medico_id = ? AND paciente_id = ?";
        $this->model->query($sql, [$medicoId, $pacienteId], "ii");
        $this->jsonResponse(["msg" => "Paciente desvinculado com sucesso"]);
    }
}
