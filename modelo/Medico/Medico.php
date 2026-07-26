<?php
require_once __DIR__ . "/../Banco.php";


class Medico
{
    private $cpf;
    private $crm;
    private $email;
    private $senha;
    private $nome;
    private $papel = "medico";
    private $instituicao;

    // Método necessário pela interface JsonSerializable para serialização do objeto para JSON
    public function jsonSerialize()
    {
        // Cria um objeto stdClass para armazenar os dados do cargo
        $objetoResposta = new stdClass();
        // Define as propriedades do objeto com os valores das propriedades da classe
        $objetoResposta->cpf = $this->cpf;
        $objetoResposta->crm = $this->crm;
        $objetoResposta->email = $this->email;
        $objetoResposta->nome = $this->nome;

        // Retorna o objeto para serialização
        return $objetoResposta;
    }

    // Método para converter a instância para um array associativo
    public function toArray()
    {
        return [
            'cpf' => $this->getcpf(),
            'crm' => $this->getcrm(),
            'email' => $this->getemail(),
            'nome' => $this->getnome(),
            'instituicao' => $this->getinstituicao()
        ];
    }

    // Cadastrar

    public function cadastrarMedico()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        if ($conexao->connect_error) {
            die("Falha na conexão: " . $conexao->connect_error);
        }

        $SQL = "INSERT INTO medico (id_instituicao, cpf, crm, email, senha, nome) VALUES (?, ?, ?, ?, md5(?), ?);";

        if ($prepareSQL = $conexao->prepare($SQL)) {
            $prepareSQL->bind_param("isssss", $this->instituicao, $this->cpf, $this->crm, $this->email, $this->senha, $this->nome);
            if ($prepareSQL->execute()) {
                $prepareSQL->close();
                return true;
            } else {
                echo "Erro na execução da consulta: " . $prepareSQL->error;
                return false;
            }
        } else {
            echo "Erro na preparação da consulta: " . $conexao->error;
            return false;
        }
    }

    // login

    public function login()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        if ($conexao->connect_error) {
            die("Falha na conexão: " . $conexao->connect_error);
        }

        $SQL = "SELECT * FROM medico WHERE email = ? AND senha = md5(?);";

        if ($prepareSQL = $conexao->prepare($SQL)) {
            $prepareSQL->bind_param("ss", $this->email, $this->senha);
            if ($prepareSQL->execute()) {

                $matrizTupla = $prepareSQL->get_result();

                if ($tupla = $matrizTupla->fetch_object()) {
                    $this->setinstituicao($tupla->id_instituicao);
                    $this->setcpf($tupla->cpf);
                    $this->setcrm($tupla->crm);
                    $this->setemail($tupla->email);
                    $this->setnome($tupla->nome);
                    return true;
                }
                $prepareSQL->close();
                return false;
            } else {
                echo "Erro na execução da consulta: " . $prepareSQL->error;
                return false;
            }
        } else {
            echo "Erro na preparação da consulta: " . $conexao->error;
            return false;
        }


    }

    // Ler tudo
/*
    public function read()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        if ($conexao->connect_error) {
            die("Falha na conexão: " . $conexao->connect_error);
        }

        $stm = $meuBanco->getConexao()->prepare("SELECT * FROM medico");
        if ($stm->execute()) {
            $resultado = $stm->get_result();
            $stm->close();

            if ($resultado->num_rows === 0) {
                return null;
            }

            $vetorMedico = array();

            while ($tupla = $resultado->fetch_object()) {
                $vetorMedico[] = [
                    "cpf" => $tupla->cpf,
                    "crm" => $tupla->crm,
                    "email" => $tupla->email,
                    "nome" => $tupla->nome
                ];
            }

            return $vetorMedico;
        } else {
            echo "Erro na execução da consulta: " . $stm->error;
            return false;
        }
    }
*/

    public function read($pagina)
    {
        $itensPorPagina = 10;
        $offset = ($pagina - 1) * $itensPorPagina;

        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        if ($conexao->connect_error) {
            die("Falha na conexão: " . $conexao->connect_error);
        }

        $SQL = "SELECT * FROM medico WHERE id_instituicao = ? ORDER BY nome ASC LIMIT ? OFFSET ?";
        $stm = $conexao->prepare($SQL);
        if ($stm) {
            $stm->bind_param("iii", $this->instituicao, $itensPorPagina, $offset);
        }


        if ($stm->execute()) {
            $resultado = $stm->get_result();
            $stm->close();

            if ($resultado->num_rows === 0) {
                return null;
            }

            $vetorMedico = array();

            while ($tupla = $resultado->fetch_object()) {
                $vetorMedico[] = [
                    "cpf" => $tupla->cpf,
                    "insituicao" => $tupla->id_instituicao,
                    "crm" => $tupla->crm,
                    "email" => $tupla->email,
                    "nome" => $tupla->nome
                ];
            }

            $stmt = $conexao->prepare("SELECT COUNT(*) as total FROM medico WHERE id_instituicao = ?");
            $stmt->bind_param("i", $this->instituicao);
            $stmt->execute();
            $result = $stmt->get_result();
            $totalRow = $result->fetch_assoc();
            $total = $totalRow['total'];
            $stmt->close();


            return [
                "status" => true,
                "msg" => "Dados encontrados",
                "medicos" => $vetorMedico,
                "total" => (int) $total
            ];

        } else {
            echo "Erro na execução da consulta: " . $stm->error;
            return false;
        }
    }

    // ler por crm  

    public function readCRM()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        if ($conexao->connect_error) {
            // Retorna erro padrão ao invés de usar die()
            error_log("Falha na conexão: " . $conexao->connect_error);
            return false;
        }

        $stm = $conexao->prepare("SELECT * FROM Medico WHERE crm = ? AND id_instituicao = ?");
        if (!$stm) {
            error_log("Erro na preparação da consulta: " . $conexao->error);
            return false;
        }

        $stm->bind_param("si", $this->crm, $this->instituicao);
        $medicos = [];

        if ($stm->execute()) {
            $resultado = $stm->get_result();

            if ($resultado && $resultado->num_rows > 0) {
                while ($linha = $resultado->fetch_object()) {
                    $medico = new Medico();
                    $medico->setCpf($linha->cpf);
                    $medico->setCrm($linha->crm);
                    $medico->setEmail($linha->email);
                    $medico->setNome($linha->nome);
                    $medicos[] = $medico;
                }
            } else {
                $stm->close();
                return null; // Nenhum médico encontrado com esse CRM
            }

            $stm->close();
            return $medicos;
        } else {
            error_log("Erro na execução da consulta: " . $stm->error);
            $stm->close();
            return false;
        }
    }


    // ler por cpf 

    public function readCPF()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        if ($conexao->connect_error) {
            die("Falha na conexão: " . $conexao->connect_error);
        }

        $stm = $conexao->prepare("SELECT * FROM medico WHERE cpf LIKE ? AND id_instituicao = ?");

        $busca = "%" . $this->cpf . "%";
        $stm->bind_param("si", $busca, $this->instituicao);

        if ($stm->execute()) {
            $resultado = $stm->get_result();
            $stm->close();

            if ($resultado->num_rows === 0) {
                return null;
            }

            $Medicos = [];

            while ($linha = $resultado->fetch_object()) {
                $Medico = new Medico();
                $Medico->setcpf($linha->cpf);
                $Medico->setNome($linha->nome);
                $Medico->setemail($linha->email);
                $Medico->setcrm($linha->crm);

                $Medicos[] = $Medico;
            }

            return $Medicos;
        } else {
            echo "Erro na execução da consulta: " . $stm->error;
            return false;
        }
    }

    public function readNome()
    {
        try {
            $meuBanco = new Banco();
            $conexao = $meuBanco->getConexao();

            if ($conexao->connect_error) {
                throw new Exception("Falha na conexão: " . $conexao->connect_error);
            }

            $sql = "SELECT * FROM Medico WHERE nome = ? AND id_instituicao = ?";
            $stm = $conexao->prepare($sql);

            if (!$stm) {
                throw new Exception("Erro ao preparar a query: " . $conexao->error);
            }

            $stm->bind_param("si", $this->nome, $this->instituicao);

            if (!$stm->execute()) {
                throw new Exception("Erro na execução da consulta: " . $stm->error);
            }

            $resultado = $stm->get_result();

            // Fecha o statement após obter o resultado
            $stm->close();

            if ($resultado->num_rows === 0) {
                return null;
            }

            $linha = $resultado->fetch_object();

            $medico = new Medico();
            $medico->setCpf($linha->cpf);
            $medico->setCrm($linha->crm);
            $medico->setEmail($linha->email);
            $medico->setNome($linha->nome);

            return $medico;
        } catch (Exception $e) {
            // Idealmente logar o erro em um arquivo em produção
            error_log("Erro em readCPF(): " . $e->getMessage());
            return false;
        }
    }

    public function readString()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        if ($conexao->connect_error) {
            die("Falha na conexão: " . $conexao->connect_error);
        }


        $sql = "SELECT * FROM medico WHERE nome LIKE ? AND id_instituicao = ?";
        $stm = $conexao->prepare($sql);

        $busca = "%" . $this->nome . "%";
        $stm->bind_param("si", $busca, $this->instituicao);

        $stm->execute();
        $resultado = $stm->get_result();
        $stm->close();

        // Se não encontrou pelo nome, tenta pelo email
        if ($resultado->num_rows === 0) {
            $sql = "SELECT * FROM medico WHERE email LIKE ? AND id_instituicao = ?";
            $stm = $conexao->prepare($sql);

            $busca = "%" . $this->nome . "%";
            $stm->bind_param("si", $busca, $this->instituicao);

            $stm->execute();
            $resultado = $stm->get_result();
            $stm->close();

            if ($resultado->num_rows === 0) {
                return null;
            }
        }

        $medicos = [];

        while ($linha = $resultado->fetch_object()) {
            $Medico = new Medico();
            $Medico->setcpf($linha->cpf);
            $Medico->setNome($linha->nome);
            $Medico->setemail($linha->email);
            $Medico->setcrm($linha->crm);

            $medicos[] = $Medico;
        }

        return $medicos;
    }
    // Sem diagnóstico

    public function readCPFSemDiagnostico()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        if ($conexao->connect_error) {
            die("Falha na conexão: " . $conexao->connect_error);
        }

        $sql = "
        SELECT m.* 
        FROM medico m
        LEFT JOIN relacao_medico_paciente rmp ON m.id = rmp.id_medico
        LEFT JOIN ia_results ia ON rmp.id_paciente = ia.id_paciente
        WHERE m.cpf LIKE ? AND ia.id_table IS NULL and m.id_insituicao = ?
    ";

        $stm = $conexao->prepare($sql);
        $busca = "%" . $this->cpf . "%";
        $stm->bind_param("si", $busca, $this->instituicao);

        if ($stm->execute()) {
            $resultado = $stm->get_result();
            $stm->close();

            if ($resultado->num_rows === 0) {
                return null;
            }

            $Medicos = [];

            while ($linha = $resultado->fetch_object()) {
                $Medico = new Medico();
                $Medico->setcpf($linha->cpf);
                $Medico->setNome($linha->nome);
                $Medico->setemail($linha->email);
                $Medico->setcrm($linha->crm);

                $Medicos[] = $Medico;
            }

            return $Medicos;
        } else {
            echo "Erro na execução da consulta: " . $stm->error;
            return false;
        }
    }

    public function readStringSemDiagnostico()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        if ($conexao->connect_error) {
            die("Falha na conexão: " . $conexao->connect_error);
        }

        $sql = "
        SELECT m.* 
        FROM medico m
        LEFT JOIN relacao_medico_paciente rmp ON m.id = rmp.id_medico
        LEFT JOIN ia_results ia ON rmp.id_paciente = ia.id_paciente
        WHERE (m.nome LIKE ? OR m.email LIKE ?) AND ia.id_table IS NULL AND m.id_insituicao = ?
    ";

        $stm = $conexao->prepare($sql);
        $busca = "%" . $this->nome . "%";
        $stm->bind_param("ssi", $busca, $busca, $this->instituicao);

        $stm->execute();
        $resultado = $stm->get_result();
        $stm->close();

        if ($resultado->num_rows === 0) {
            return null;
        }

        $medicos = [];

        while ($linha = $resultado->fetch_object()) {
            $Medico = new Medico();
            $Medico->setcpf($linha->cpf);
            $Medico->setNome($linha->nome);
            $Medico->setemail($linha->email);
            $Medico->setcrm($linha->crm);

            $medicos[] = $Medico;
        }

        return $medicos;
    }



    // atualizar por cpf

    public function update()
    {
        try {
            $meuBanco = new Banco();
            $conexao = $meuBanco->getConexao();

            if ($conexao->connect_error) {
                throw new Exception("Falha na conexão: " . $conexao->connect_error);
            }

            $sql = "UPDATE medico SET crm = ?, nome = ?, email = ?, senha = md5(?) WHERE cpf = ? AND id_instituicao = ?";
            $stm = $conexao->prepare($sql);

            if (!$stm) {
                throw new Exception("Erro ao preparar a consulta: " . $conexao->error);
            }

            $stm->bind_param("sssssi", $this->crm, $this->nome, $this->email, $this->senha, $this->cpf, $this->instituicao);

            if ($stm->execute()) {
                if ($stm->affected_rows === 0) {
                    // Nenhuma linha atualizada = CPF não encontrado
                    $stm->close();
                    return null;
                }
                $stm->close();
                return true;
            } else {
                throw new Exception("Erro na execução da consulta: " . $stm->error);
            }

        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }



    // deletar por cpf

    public function delete()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        if ($conexao->connect_error) {
            die("Falha na conexão: " . $conexao->connect_error);
        }

        $SQL = "DELETE FROM medico WHERE cpf = ? and id_instituicao = ?;";


        if ($prepareSQL = $conexao->prepare($SQL)) {
            $prepareSQL->bind_param("si", $this->cpf, $this->instituicao);
            if ($prepareSQL->execute()) {
                $prepareSQL->close();
                return true;
            } else {
                echo "Erro na execução da consulta: " . $prepareSQL->error;
                return false;
            }
        } else {
            echo "Erro na preparação da consulta: " . $conexao->error;
            return false;
        }
    }

    /*  GETTERS E SETTERS */

    // Getter e Setter para cpf
    public function getcpf()
    {
        return $this->cpf;
    }

    public function setcpf($cpf)
    {
        $this->cpf = $cpf;
    }

    // Getter e Setter para crm
    public function getcrm()
    {
        return $this->crm;
    }

    public function setcrm($crm)
    {
        $this->crm = $crm;
    }

    // Getter e Setter para email
    public function getemail()
    {
        return $this->email;
    }

    public function setemail($email)
    {
        $this->email = $email;
    }

    // Getter e Setter para senha
    public function getsenha()
    {
        return $this->senha;
    }

    public function setsenha($senha)
    {
        $this->senha = $senha;
    }

    // Getter e Setter para nome
    public function getnome()
    {
        return $this->nome;
    }

    public function setnome($nome)
    {
        $this->nome = $nome;
    }
    // PAPEL


    public function getPapel()
    {
        return $this->papel;
    }

    public function setinstituicao($instituicao)
    {
        $this->instituicao = $instituicao;
    }
    public function getinstituicao()
    {
        return $this->instituicao;
    }
}
?>