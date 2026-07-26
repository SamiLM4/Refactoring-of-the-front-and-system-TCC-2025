<?php

require_once __DIR__ . "/BaseModel.php";

class MedicoModel extends BaseModel {
    protected $table = 'usuarios';

    public function getAll($instituicaoId) {
        $sql = "
            SELECT u.* 
            FROM usuarios u
            JOIN usuarios_papeis up ON up.usuario_id = u.id
            JOIN papeis p ON p.id = up.papel_id
            WHERE u.instituicao_id = ? 
            AND p.nome = 'MEDICO'
            AND u.deleted_at IS NULL
        ";
        $result = $this->query($sql, [$instituicaoId], "i");
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    public function getById($id, $instituicaoId) {
        $sql = "
            SELECT u.* 
            FROM usuarios u
            JOIN usuarios_papeis up ON up.usuario_id = u.id
            JOIN papeis p ON p.id = up.papel_id
            WHERE u.id = ? 
            AND u.instituicao_id = ? 
            AND p.nome = 'MEDICO'
            AND u.deleted_at IS NULL
        ";
        $result = $this->query($sql, [$id, $instituicaoId], "ii");
        return $result->fetch_assoc();
    }

    public function getByCrm($crm, $instituicaoId) {
        $sql = "
            SELECT u.* 
            FROM usuarios u
            JOIN usuarios_papeis up ON up.usuario_id = u.id
            JOIN papeis p ON p.id = up.papel_id
            WHERE u.crm = ? 
            AND u.instituicao_id = ? 
            AND p.nome = 'MEDICO'
            AND u.deleted_at IS NULL
        ";
        $result = $this->query($sql, [$crm, $instituicaoId], "si");
        return $result->fetch_assoc();
    }

    public function create($data) {
        $usuarioId = $data['usuario_id'];
        $instituicaoId = $data['instituicao_id'];
        unset($data['usuario_id'], $data['instituicao_id']);

        $fields = "";
        $params = [];
        $types = "";

        foreach ($data as $key => $value) {
            $fields .= "{$key} = ?, ";
            $params[] = $value;
            $types .= "s";
        }
        $fields = rtrim($fields, ", ");

        $sql = "UPDATE usuarios SET {$fields} WHERE id = ? AND instituicao_id = ?";
        $params[] = $usuarioId;
        $params[] = $instituicaoId;
        $types .= "ii";

        $this->query($sql, $params, $types);
        return $usuarioId;
    }

    public function update($id, $instituicaoId, $data) {
        $fields = "";
        foreach ($data as $key => $value) {
            $fields .= "{$key} = ?, ";
        }
        $fields = rtrim($fields, ", ");
        $sql = "UPDATE usuarios SET {$fields} WHERE id = ? AND instituicao_id = ?";
        $params = array_values($data);
        $params[] = $id;
        $params[] = $instituicaoId;
        return $this->query($sql, $params);
    }

    public function delete($id, $instituicaoId) {
        $sql = "UPDATE usuarios SET deleted_at = NOW() WHERE id = ? AND instituicao_id = ?";
        return $this->query($sql, [$id, $instituicaoId], "ii");
    }
}
