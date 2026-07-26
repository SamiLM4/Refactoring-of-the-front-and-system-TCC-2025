<?php

abstract class BaseModel {
    protected $db;
    protected $table;

    public function __construct() {
        require_once __DIR__ . "/Banco.php";
        $banco = new Banco();
        $this->db = $banco->getConexao();
    }

    public function getDb() {
        return $this->db;
    }

    public function setDb($db) {
        $this->db = $db;
    }

    public function query($sql, $params = [], $types = "") {
        if (empty($params)) {
            $result = $this->db->query($sql);
            if ($result === false) {
                throw new Exception("Erro SQL: " . $this->db->error);
            }
            return $result;
        }
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro ao preparar query: " . $this->db->error);
        }
        if ($types === "") {
            $types = str_repeat("s", count($params));
        }
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            throw new Exception("Erro ao executar query: " . $stmt->error);
        }
        return $stmt->get_result();
    }
}
