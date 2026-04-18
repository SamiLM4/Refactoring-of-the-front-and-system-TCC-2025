<?php
require_once __DIR__ . "/../Banco.php";

class Adm
{
    private $id;
    private $nome;
    private $email;
    private $senha;
    private $papel = "adm";
    private $instituicao;

    public function jsonSerialize()
    {
        $resposta = new stdClass();
        $resposta->id = $this->id;
        $resposta->nome = $this->nome;
        $resposta->email = $this->email;
        return $resposta;
    }

    public function toArray()
    {
        return [
            'id' => $this->getid(),
            'nome' => $this->getnome(),
            'email' => $this->getemail()
        ];
    }

    // Cadastrar novo administrador
    public function cadastrarAdm()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        if ($conexao->connect_error) {
            die("Falha na conexão: " . $conexao->connect_error);
        }

        $SQL = "INSERT INTO adm(id_instituicao, nome, email, senha) VALUES (?, ?, ?, md5(?));";

        if ($stmt = $conexao->prepare($SQL)) {
            $stmt->bind_param("isss", $this->instituicao, $this->nome, $this->email, $this->senha);
            if ($stmt->execute()) {
                $stmt->close();
                return true;
            } else {
                echo "Erro na execução: " . $stmt->error;
                return false;
            }
        } else {
            echo "Erro na preparação: " . $conexao->error;
            return false;
        }
    }

    // Login de administrador
    public function login()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        $SQL = "SELECT * FROM adm WHERE email = ? AND senha = md5(?);";

        if ($stmt = $conexao->prepare($SQL)) {
            $stmt->bind_param("ss", $this->email, $this->senha);
            if ($stmt->execute()) {
                $resultado = $stmt->get_result();
                if ($linha = $resultado->fetch_object()) {
                    $this->setid($linha->id);
                    $this->setinstituicao($linha->id_instituicao);
                    $this->setnome($linha->nome);
                    $this->setemail($linha->email);
                    return true;
                }
                $stmt->close();
                return false;
            } else {
                echo "Erro na execução: " . $stmt->error;
                return false;
            }
        } else {
            echo "Erro na preparação: " . $conexao->error;
            return false;
        }
    }

    public function read($pagina, $instituicao)
    {
        $itensPorPagina = 10;
        $offset = ($pagina - 1) * $itensPorPagina;

        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        // Consulta paginada
        $SQL = "SELECT * FROM adm WHERE id_instituicao = ? ORDER BY nome ASC LIMIT ? OFFSET ?;";

        $stmt = $conexao->prepare($SQL);

        if ($stmt) {
            $stmt->bind_param("iii", $instituicao, $itensPorPagina, $offset);

            if ($stmt->execute()) {
                $result = $stmt->get_result();
                $stmt->close();

                $adms = [];
                while ($linha = $result->fetch_object()) {
                    $adms[] = [
                        "id" => $linha->id,
                        "instituicao" => $linha->id_instituicao,
                        "nome" => $linha->nome,
                        "email" => $linha->email
                    ];
                }

                // Consultar o total de administradores
                $stmt = $conexao->prepare("SELECT COUNT(*) as total FROM adm WHERE id_instituicao = ?");
                $stmt->bind_param("i", $instituicao);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $total = $row['total'];


                return [
                    "status" => true,
                    "msg" => "Dados encontrados",
                    "administradores" => $adms,
                    "total" => (int) $total
                ];
            } else {
                echo "Erro na execução: " . $stmt->error;
                return false;
            }
        } else {
            echo "Erro ao preparar statement: " . $conexao->error;
            return false;
        }
    }    
    
    public function readID()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        if ($conexao->connect_error) {
            die("Falha na conexão: " . $conexao->connect_error);
        }

        $SQL = "SELECT * FROM adm WHERE id = ?;";

        if ($stmt = $conexao->prepare($SQL)) {
            $stmt->bind_param("i", $this->id);
            if ($stmt->execute()) {
                $resultado = $stmt->get_result();
                if ($linha = $resultado->fetch_object()) {
                    $this->setid($linha->id);
                    $this->setnome($linha->nome);
                    $this->setemail($linha->email);
                    $this->setinstituicao($linha->id_instituicao);
                    $stmt->close();
                    return true;
                }
                $stmt->close();
                return false;
            } else {
                echo "Erro na execução: " . $stmt->error;
                return false;
            }
        } else {
            echo "Erro na preparação: " . $conexao->error;
            return false;
        }
    }


    
    public function readString($instituicao)
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        if ($conexao->connect_error) {
            die("Falha na conexão: " . $conexao->connect_error);
        }

        $sql = "SELECT * FROM adm WHERE nome LIKE ? and id_instituicao = ?";
        $stm = $conexao->prepare($sql);

        $busca = "%" . $this->nome . "%";
        $stm->bind_param("si", $busca, $instituicao);

        $stm->execute();
        $resultado = $stm->get_result();
        $stm->close();

        // Se não encontrou pelo nome, tenta pelo email
        if ($resultado->num_rows === 0) {
            $sql = "SELECT * FROM adm WHERE email LIKE ? and id_instituicao = ?";
            $stm = $conexao->prepare($sql);

            $busca = "%" . $this->nome . "%";
            $stm->bind_param("si", $busca, $instituicao);

            $stm->execute();
            $resultado = $stm->get_result();
            $stm->close();

            if ($resultado->num_rows === 0) {
                return null;
            }
        }

        $adms = [];

        while ($linha = $resultado->fetch_object()) {
            $Adm = new Adm();
            $Adm->setid($linha->id);
            $Adm->setNome($linha->nome);
            $Adm->setemail($linha->email);
        
            $adms[] = $Adm;
        }

        return $adms;
    }
    

    // Atualizar administrador por ID
    public function update()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        $SQL = "UPDATE adm SET nome = ?, email = ?, senha = md5(?) WHERE id = ? AND id_instituicao = ?;";
        $stmt = $conexao->prepare($SQL);
        $stmt->bind_param("sssii", $this->nome, $this->email, $this->senha, $this->id, $this->instituicao);

        if ($stmt->execute()) {
            $stmt->close();
            return true;
        } else {
            echo "Erro na execução: " . $stmt->error;
            return false;
        }
    }

    // Deletar administrador por ID
    public function delete()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        $SQL = "DELETE FROM adm WHERE id = ? AND id_instituicao = ?;";
        if ($stmt = $conexao->prepare($SQL)) {
            $stmt->bind_param("ii", $this->id, $this->instituicao);
            if ($stmt->execute()) {
                $stmt->close();
                return true;
            } else {
                echo "Erro na execução: " . $stmt->error;
                return false;
            }
        } else {
            echo "Erro na preparação: " . $conexao->error;
            return false;
        }
    }

    // Getters e Setters
    public function getid()
    {
        return $this->id;
    }

    public function setid($id)
    {
        $this->id = $id;
    }

    public function getnome()
    {
        return $this->nome;
    }

    public function setnome($nome)
    {
        $this->nome = $nome;
    }

    public function getemail()
    {
        return $this->email;
    }

    public function setemail($email)
    {
        $this->email = $email;
    }

    public function getsenha()
    {
        return $this->senha;
    }

    public function setsenha($senha)
    {
        $this->senha = $senha;
    }

    public function getPapel()
    {
        return $this->papel;
    }

    public function setinstituicao($instituicao) {
        $this->instituicao = $instituicao;
    }

    public function getinstituicao() {
        return $this->instituicao;
    }
}
?>