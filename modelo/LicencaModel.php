<?php

require_once __DIR__ . "/BaseModel.php";

class LicencaModel extends BaseModel {
    protected $table = 'licencas';

    public function createLicense($planoId, $token, $expiraEm) {
        $sql = "INSERT INTO {$this->table} (token, plano_id, status, usado, expira_em, criado_em) VALUES (?, ?, 'ativa', 0, ?, NOW())";
        return $this->query($sql, [$token, $planoId, $expiraEm], "sis");
    }

    public function getByToken($token) {
        $sql = "SELECT * FROM {$this->table} WHERE token = ? FOR UPDATE";
        $result = $this->query($sql, [$token], "s");
        return $result->fetch_assoc();
    }

    public function markAsUsed($id, $instituicaoId) {
        $sql = "UPDATE {$this->table} SET usado = TRUE, instituicao_id = ? WHERE id = ?";
        return $this->query($sql, [$instituicaoId, $id], "ii");
    }

    public function getLimiteUsuarios($instituicaoId) {
        $sql = "SELECT p.limite_usuarios 
                FROM {$this->table} l
                JOIN planos p ON l.plano_id = p.id
                WHERE l.instituicao_id = ? AND l.usado = 1 AND l.status = 'ativa'
                LIMIT 1";
        $result = $this->query($sql, [$instituicaoId], "i");
        $row = $result->fetch_assoc();
        return $row ? (int)$row['limite_usuarios'] : 0;
    }
}
