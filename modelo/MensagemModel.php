<?php

require_once __DIR__ . "/BaseModel.php";

class MensagemModel extends BaseModel {
    protected $table = 'mensagens_chat';

    public function create($data) {
        $fields = implode(", ", array_keys($data));
        $placeholders = implode(", ", array_fill(0, count($data), "?"));
        $sql = "INSERT INTO {$this->table} ({$fields}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));
        return $this->db->insert_id;
    }

    public function getConversa($usuarioMe, $usuarioOutro, $instituicaoId) {
        $sql = "
            SELECT 
                mc.*, 
                u_de.email as remetente_email,
                u_de.nome as remetente_nome
            FROM mensagens_chat mc
            JOIN usuarios u_de ON u_de.id = mc.usuario_id
            WHERE mc.instituicao_id = ? 
              AND mc.deleted_at IS NULL
              AND (
                  (mc.usuario_id = ? AND mc.para_usuario_id = ?)
                  OR 
                  (mc.usuario_id = ? AND mc.para_usuario_id = ?)
              )
            ORDER BY mc.data_envio ASC
        ";
        $result = $this->query($sql, [$instituicaoId, $usuarioMe, $usuarioOutro, $usuarioOutro, $usuarioMe], "iiiii");
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    public function getUltimasMensagens($usuarioId, $instituicaoId) {
        // Busca a última mensagem de cada "parceiro" de conversa
        $sql = "
            SELECT mc.*, 
                   u.email as outro_email,
                   u.nome as outro_nome
            FROM mensagens_chat mc
            JOIN (
                SELECT 
                    CASE WHEN usuario_id = ? THEN para_usuario_id ELSE usuario_id END as parceiro_id,
                    MAX(id) as max_id
                FROM mensagens_chat
                WHERE (usuario_id = ? OR para_usuario_id = ?) AND instituicao_id = ?
                GROUP BY parceiro_id
            ) last_msgs ON mc.id = last_msgs.max_id
            JOIN usuarios u ON u.id = last_msgs.parceiro_id
            ORDER BY mc.data_envio DESC
        ";
        $result = $this->query($sql, [$usuarioId, $usuarioId, $usuarioId, $instituicaoId], "iiii");
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    public function marcarLida($id, $instituicaoId) {
        $sql = "UPDATE {$this->table} SET lida = 1 WHERE id = ? AND instituicao_id = ?";
        return $this->query($sql, [$id, $instituicaoId], "ii");
    }
}
