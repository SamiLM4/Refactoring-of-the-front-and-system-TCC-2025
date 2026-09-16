<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

class Banco
{
    private $host = "127.0.0.1";
    private $usuario = "root";
    private $senha = "";
    private $banco = "TCC25";
    private $porta = "3306";
    private $con = null;

    public function conectar()
    {
        $this->host = $_ENV['DB_HOST'] ?? "127.0.0.1";
        $this->usuario = $_ENV['DB_USER'] ?? "root";
        $this->senha = $_ENV['DB_PASS'] ?? "";
        $this->banco = $_ENV['DB_NAME'] ?? "TCC25";
        $this->porta = $_ENV['DB_PORT'] ?? "3306";

        try {
            $this->con = new mysqli($this->host, $this->usuario, $this->senha, $this->banco, $this->porta);
        } catch (\mysqli_sql_exception $e) {
            http_response_code(500);
            header("Content-Type: application/json");
            echo json_encode([
                "status" => "erro",
                "cod" => "1",
                "msg" => "Erro ao estabelecer conexão: " . $e->getMessage(),
            ]);
            die();
        }
    }

    public function getConexao()
    {
        if ($this->con == null) {
            $this->conectar();
        }
        return $this->con;
    }

    public function setConexao($conexao)
    {
        $this->con = $conexao;
        return $this->con;
    }
}
?>