<?php

require_once __DIR__ . "/BaseController.php";
require_once __DIR__ . "/../modelo/UsuarioModel.php";
require_once __DIR__ . "/../modelo/MedicoModel.php";
require_once __DIR__ . "/../modelo/PacienteModel.php";

class UsuarioController extends BaseController {
    private $model;

    public function __construct() {
        $this->model = new UsuarioModel();
    }

    public function store() {
        $usuarioLogado = $GLOBALS['usuario'];
        $instituicaoId = $usuarioLogado['instituicao_id'];
        
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        // --- License Limit Check ---
        require_once __DIR__ . "/../modelo/LicencaModel.php";
        $licencaModel = new LicencaModel();
        $limite = $licencaModel->getLimiteUsuarios($instituicaoId);
        
        // Count active users
        $resCount = $this->model->query("SELECT COUNT(*) as total FROM usuarios WHERE instituicao_id = ? AND ativo = 1", [$instituicaoId], "i");
        $atual = $resCount->fetch_assoc()['total'] ?? 0;

        if ($limite > 0 && $atual >= $limite) {
            $this->errorResponse("Limite de usuários atingido para o seu plano ({$limite}). Entre em contato com o suporte para aumentar seu limite.");
        }
        // ---------------------------

        if (empty($data['email']) || empty($data['senha']) || empty($data['papel_id'])) {
            $this->errorResponse("Email, senha e papel são obrigatórios");
        }

        $email = $data['email'];
        $senhaHash = password_hash($data['senha'], PASSWORD_DEFAULT);
        $papelId = $data['papel_id'];

        $this->model->beginTransaction();

        try {
            // 1. Create User
            $usuarioId = $this->model->create([
                'email' => $data['email'],
                'senha_hash' => password_hash($data['senha'], PASSWORD_DEFAULT),
                'instituicao_id' => $instituicaoId,
                'nome' => $data['nome'] ?? null,
                'cpf' => $data['cpf'] ?? null
            ]);

            // 2. Assign Role
            $this->model->assignRole($usuarioId, $papelId, $instituicaoId);

            $roleRes = $this->model->query("SELECT nome FROM papeis WHERE id = ?", [$papelId], "i");
            $roleName = strtoupper(trim($roleRes->fetch_assoc()['nome'] ?? ''));

            if ($roleName === 'MEDICO') {
                if (empty($data['nome']) || empty($data['cpf']) || empty($data['crm'])) {
                    throw new Exception("Nome, CPF e CRM são obrigatórios para médicos");
                }
                $medicoModel = new MedicoModel();
                $medicoModel->setDb($this->model->getDb());
                $medicoModel->create([
                    'usuario_id' => $usuarioId,
                    'instituicao_id' => $instituicaoId,
                    'nome' => $data['nome'],
                    'cpf' => $data['cpf'],
                    'crm' => $data['crm']
                ]);
            } elseif ($roleName === 'PACIENTE') {
                if (empty($data['nome']) || empty($data['cpf'])) {
                    throw new Exception("Nome e CPF são obrigatórios para pacientes");
                }
                $pacienteModel = new PacienteModel();
                $pacienteModel->setDb($this->model->getDb());
                $pacienteData = [
                    'usuario_id' => $usuarioId,
                    'instituicao_id' => $instituicaoId,
                    'nome' => $data['nome'],
                    'cpf' => $data['cpf'],
                    'sexo' => $data['sexo'] ?? null,
                    'endereco' => $data['endereco'] ?? null,
                    'telefone' => $data['telefone'] ?? null,
                    'profissao' => $data['profissao'] ?? null,
                    'estado_civil' => $data['estado_civil'] ?? null,
                    'nome_cuidador' => $data['nome_cuidador'] ?? null,
                    'telefone_cuidador' => $data['telefone_cuidador'] ?? null
                ];
                $pacienteModel->create($pacienteData);
            } elseif ($roleName === 'ADMIN') {
                if (empty($data['nome'])) {
                    throw new Exception("Nome é obrigatório para administradores");
                }
                $this->model->createAdminRecord($usuarioId, $instituicaoId, $data['nome']);
            }


            $this->model->commit();
            $emailLog = $data['email'] ?? 'desconhecido';
            $this->registrarAuditoria('Criou Usuário', "Criou o usuário com email: " . $emailLog);
            $this->jsonResponse(["id" => $usuarioId, "msg" => "Usuário e perfil criados com sucesso"], true, 201);

        } catch (Exception $e) {
            $this->model->rollback();
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function list() {
        $usuarioLogado = $GLOBALS['usuario'];
        $instituicaoId = $usuarioLogado['instituicao_id'];
        
        $sql = "
            SELECT u.id, u.email, u.ativo, u.criado_em, 
                   GROUP_CONCAT(p.nome) as papeis,
                   GROUP_CONCAT(p.id) as papeis_ids
            FROM usuarios u
            LEFT JOIN usuarios_papeis up ON up.usuario_id = u.id
            LEFT JOIN papeis p ON p.id = up.papel_id
            WHERE u.instituicao_id = ? AND u.deleted_at IS NULL
            GROUP BY u.id
        ";
        $result = $this->model->query($sql, [$instituicaoId], "i");
        $data = [];
        while ($row = $result->fetch_assoc()) $data[] = $row;
        $this->jsonResponse($data);
    }

    public function show($id) {
        $usuarioLogado = $GLOBALS['usuario'];
        $instituicaoId = $usuarioLogado['instituicao_id'];
        
        $sql = "
            SELECT u.*, 
                   GROUP_CONCAT(p.id) as papel_ids,
                   GROUP_CONCAT(p.nome) as papeis
            FROM usuarios u
            LEFT JOIN usuarios_papeis up ON up.usuario_id = u.id
            LEFT JOIN papeis p ON p.id = up.papel_id
            WHERE u.id = ? AND u.instituicao_id = ? AND u.deleted_at IS NULL
            GROUP BY u.id
        ";
        $result = $this->model->query($sql, [$id, $instituicaoId], "ii");
        $data = $result->fetch_assoc();
        
        if (!$data) {
            $this->errorResponse("Usuário não encontrado", 404);
        }

        // Get single papel_id for the form
        $papeisIds = explode(',', $data['papel_ids'] ?? '');
        $data['papel_id'] = $papeisIds[0] ?? null;

        // Check for Patient Profile
        $pacienteModel = new PacienteModel();
        $pacienteModel->setDb($this->model->getDb());
        $sqlPaciente = "SELECT * FROM pacientes WHERE usuario_id = ? AND deleted_at IS NULL";
        $resPaciente = $this->model->query($sqlPaciente, [$id], "i");
        $paciente = $resPaciente->fetch_assoc();
        if ($paciente) {
            $data['paciente'] = $paciente;
        }

        // Check for Admin Profile
        $sqlAdmin = "SELECT * FROM admins WHERE usuario_id = ? AND deleted_at IS NULL";
        $resAdmin = $this->model->query($sqlAdmin, [$id], "i");
        $admin = $resAdmin->fetch_assoc();
        if ($admin) {
            $data['admin'] = $admin;
        }
        
        $this->jsonResponse($data);
    }

    public function update($id) {
        $usuarioLogado = $GLOBALS['usuario'];
        $instituicaoId = $usuarioLogado['instituicao_id'];
        
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (!$data) $this->errorResponse("Dados inválidos");

        $this->model->beginTransaction();
        try {
            $updateData = [];
            $updateTypes = '';
            $updateValues = [];

            if (!empty($data['email'])) {
                $updateData[] = 'email = ?';
                $updateTypes .= 's';
                $updateValues[] = $data['email'];
            }
            if (!empty($data['senha'])) {
                $updateData[] = 'senha_hash = ?';
                $updateTypes .= 's';
                $updateValues[] = password_hash($data['senha'], PASSWORD_DEFAULT);
            }
            if (isset($data['ativo'])) {
                $updateData[] = 'ativo = ?';
                $updateTypes .= 'i';
                $updateValues[] = (int)$data['ativo'];
            }
            if (isset($data['nome'])) {
                $updateData[] = 'nome = ?';
                $updateTypes .= 's';
                $updateValues[] = $data['nome'];
            }
            if (isset($data['cpf'])) {
                $updateData[] = 'cpf = ?';
                $updateTypes .= 's';
                $updateValues[] = $data['cpf'];
            }
            // Note: crm and especialidade are typically for Medico, not directly on Usuario.
            // If these are intended to update the main user table, ensure the schema supports it.
            // For now, I'll add them as per the instruction, assuming they are user fields.
            if (isset($data['crm'])) {
                $updateData[] = 'crm = ?';
                $updateTypes .= 's';
                $updateValues[] = $data['crm'];
            }
            if (isset($data['especialidade'])) {
                $updateData[] = 'especialidade = ?';
                $updateTypes .= 's';
                $updateValues[] = $data['especialidade'];
            }

            if (!empty($updateData)) {
                $sql = "UPDATE usuarios SET " . implode(', ', $updateData) . " WHERE id = ? AND instituicao_id = ?";
                $updateTypes .= 'ii';
                $updateValues[] = $id;
                $updateValues[] = $instituicaoId;
                $this->model->query($sql, $updateValues, $updateTypes);
            }
            
            // Update roles if provided
            if (isset($data['papel_id'])) {
                // Remove existing roles
                $this->model->query("DELETE FROM usuarios_papeis WHERE usuario_id = ? AND instituicao_id = ?", [$id, $instituicaoId], "ii");
                // Assign new role
                if ($data['papel_id']) {
                    $this->model->assignRole($id, $data['papel_id'], $instituicaoId);

                    // Sync admin_owner flag based on the new role
                    $roleRes = $this->model->query("SELECT nome FROM papeis WHERE id = ?", [$data['papel_id']], "i");
                    $roleName = strtoupper(trim($roleRes->fetch_assoc()['nome'] ?? ''));
                    if ($roleName === 'ADMIN') {
                        $this->model->query("UPDATE usuarios SET admin_owner = 1 WHERE id = ?", [$id], "i");
                    } else {
                        $this->model->resetAdminStatus($id, $instituicaoId);
                    }
                }
            }

            // Update Profile (Paciente)
            $pacienteModel = new PacienteModel();
            $pacienteModel->setDb($this->model->getDb());
            $sqlPac = "SELECT id FROM pacientes WHERE usuario_id = ? AND deleted_at IS NULL";
            $resPac = $this->model->query($sqlPac, [$id], "i");
            $paciente = $resPac->fetch_assoc();

            if ($paciente) {
                $pacienteUpdate = [];
                $fields = ['nome', 'cpf', 'sexo', 'endereco', 'telefone', 'profissao', 'estado_civil', 'nome_cuidador', 'telefone_cuidador'];
                foreach ($fields as $f) {
                    if (isset($data[$f])) $pacienteUpdate[$f] = $data[$f];
                }
                if (!empty($pacienteUpdate)) {
                    $pacienteModel->update($paciente['id'], $instituicaoId, $pacienteUpdate);
                }
            }

            // Update Profile (Admin)
            $sqlAdm = "SELECT id FROM admins WHERE usuario_id = ? AND deleted_at IS NULL";
            $resAdm = $this->model->query($sqlAdm, [$id], "i");
            $admin = $resAdm->fetch_assoc();

            if ($admin) {
                $adminUpdate = [];
                $fields = ['nome'];
                foreach ($fields as $f) {
                    if (isset($data[$f])) $adminUpdate[$f] = $data[$f];
                }
                if (!empty($adminUpdate)) {
                    $this->model->query("UPDATE admins SET nome = ? WHERE id = ?", [$adminUpdate['nome'], $admin['id']], "si");
                }
            }
            
            $this->model->commit();
            $this->registrarAuditoria('Editou Usuário', "Editou o usuário ID: " . $id);
            $this->jsonResponse(["msg" => "Usuário atualizado com sucesso"]);
        } catch (Exception $e) {
            $this->model->rollback();
            $this->errorResponse($e->getMessage());
        }
    }

    public function delete($id) {
        $usuarioLogado = $GLOBALS['usuario'];
        $instituicaoId = $usuarioLogado['instituicao_id'];

        $this->model->beginTransaction();
        try {
            // 1. Soft delete the user
            $sql = "UPDATE usuarios SET deleted_at = NOW(), ativo = 0 WHERE id = ? AND instituicao_id = ? AND deleted_at IS NULL";
            $this->model->query($sql, [$id, $instituicaoId], "ii");

            // 2. Soft delete any associated Patient profile
            $pacienteModel = new PacienteModel();
            $pacienteModel->setDb($this->model->getDb());
            $sqlPac = "UPDATE pacientes SET deleted_at = NOW() WHERE usuario_id = ? AND instituicao_id = ? AND deleted_at IS NULL";
            $this->model->query($sqlPac, [$id, $instituicaoId], "ii");

            // 3. Soft delete any associated Admin profile
            $sqlAdm = "UPDATE admins SET deleted_at = NOW() WHERE usuario_id = ? AND instituicao_id = ? AND deleted_at IS NULL";
            $this->model->query($sqlAdm, [$id, $instituicaoId], "ii");

            // 4. Optionally remove role assignments (or keep them for audit, but soft-deleting the user is enough for access control)
            // We'll keep them to maintain historical record of what role the user had.

            $this->model->commit();
            $this->registrarAuditoria('Deletou Usuário', "Excluiu o usuário ID: " . $id);
            $this->jsonResponse(["msg" => "Usuário removido com sucesso (Safe Delete)"]);
        } catch (Exception $e) {
            $this->model->rollback();
            $this->errorResponse($e->getMessage());
        }
    }
}
