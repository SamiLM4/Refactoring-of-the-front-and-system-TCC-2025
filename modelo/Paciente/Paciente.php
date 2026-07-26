<?php
require_once __DIR__ . '/../Banco.php';

class Paciente
{
    private $id;
    private $cpf;
    private $nome;
    private $sexo;
    private $endereco;
    private $telefone;
    private $email;
    private $profissao;
    private $estado_civil;
    private $nome_cuidador;
    private $telefone_cuidador;
    private $instituicao;

    public function jsonSerialize()
    {
        $objetoResposta = new stdClass();

        $objetoResposta->nome = $this->nome;
        $objetoResposta->cpf = $this->cpf;
        $objetoResposta->sexo = $this->sexo;
        $objetoResposta->profissao = $this->profissao;
        $objetoResposta->estado_civil = $this->estado_civil;
        $objetoResposta->nome_cuidador = $this->nome_cuidador;
        $objetoResposta->telefone = $this->telefone;
        $objetoResposta->email = $this->email;
        $objetoResposta->instituicao = $this->instituicao;
        $objetoResposta->endereco = $this->endereco;
        $objetoResposta->telefone_cuidador = $this->telefone_cuidador;

        return $objetoResposta;
    }


    // cadastrar paciente

    public function cadastrarPaciente()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        if ($conexao->connect_error) {
            die("Falha na conexão: " . $conexao->connect_error);
        }

        $SQL = "INSERT INTO paciente (id_instituicao, cpf, nome, sexo, endereco, telefone, email, profissao, estado_civil, nome_cuidador, telefone_cuidador) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        if ($prepareSQL = $conexao->prepare($SQL)) {
            $prepareSQL->bind_param("issssssssss", $this->instituicao, $this->cpf, $this->nome, $this->sexo, $this->endereco, $this->telefone, $this->email, $this->profissao, $this->estado_civil, $this->nome_cuidador, $this->telefone_cuidador);
            ;
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

    // ler tudo
/*
    public function read()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        if ($conexao->connect_error) {
            die("Falha na conexão: " . $conexao->connect_error);
        }

        $stm = $conexao->prepare("SELECT * FROM paciente");

        if ($stm->execute()) {
            $resultado = $stm->get_result();
            $stm->close();

            if ($resultado->num_rows === 0) {
                return null;
            }

            $vetorPaciente = [];

            while ($tupla = $resultado->fetch_object()) {
                $pacienteDados = new Paciente();

                $pacienteDados->setCpf($tupla->cpf);
                $pacienteDados->setNome($tupla->nome);
                $pacienteDados->setSexo($tupla->sexo);
                $pacienteDados->setEndereco($tupla->endereco);
                $pacienteDados->setTelefone($tupla->telefone);
                $pacienteDados->setEmail($tupla->email);
                $pacienteDados->setProfissao($tupla->profissao);
                $pacienteDados->setEstadoCivil($tupla->estado_civil);
                $pacienteDados->setNomeCuidador($tupla->nome_cuidador);
                $pacienteDados->setTelefoneCuidador($tupla->telefone_cuidador);

                $vetorPaciente[] = $pacienteDados;
            }

            return $vetorPaciente;
        } else {
            error_log("Erro na execução da consulta: " . $stm->error);
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

        $SQL = "SELECT * FROM paciente WHERE id_instituicao = ? ORDER BY nome ASC LIMIT ? OFFSET ?";
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

            $vetorPaciente = [];

            while ($tupla = $resultado->fetch_object()) {
                $vetorPaciente[] = [
                    "cpf" => $tupla->cpf,
                    "nome" => $tupla->nome,
                    "sexo" => $tupla->sexo,
                    "endereco" => $tupla->endereco,
                    "telefone" => $tupla->telefone,
                    "email" => $tupla->email,
                    "profissao" => $tupla->profissao,
                    "estado_civil" => $tupla->estado_civil,
                    "nome_cuidador" => $tupla->nome_cuidador,
                    "telefone_cuidador" => $tupla->telefone_cuidador
                ];
            }

            $totalSQL = "SELECT COUNT(*) as total FROM paciente WHERE id_instituicao = ?";

            $stmt = $conexao->prepare($totalSQL);
            $stmt->bind_param("i", $this->instituicao);
            $stmt->execute();
            $result = $stmt->get_result();
            $totalRow = $result->fetch_assoc();
            $total = $totalRow['total'];
            $stmt->close();


            return [
                "status" => true,
                "msg" => "Dados encontrados",
                "pacientes" => $vetorPaciente,
                "total" => (int) $total
            ];
        } else {
            error_log("Erro na execução da consulta: " . $stm->error);
            return false;
        }
    }



    public function readDiagnosticos($pagina)
    {
        $itensPorPagina = 10;
        $offset = ($pagina - 1) * $itensPorPagina;

        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        if ($conexao->connect_error) {
            die("Falha na conexão: " . $conexao->connect_error);
        }

        $SQL = "
                SELECT p.*
                FROM paciente p
                WHERE NOT EXISTS (
                    SELECT *
                    FROM ia_results ia
                    WHERE ia.id_paciente = p.id
                )  AND p.id_instituicao = ? ORDER BY nome ASC LIMIT ? OFFSET ?;
            ";
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

            $vetorPaciente = [];

            while ($tupla = $resultado->fetch_object()) {
                $vetorPaciente[] = [
                    "cpf" => $tupla->cpf,
                    "nome" => $tupla->nome,
                    "sexo" => $tupla->sexo,
                    "endereco" => $tupla->endereco,
                    "telefone" => $tupla->telefone,
                    "email" => $tupla->email,
                    "profissao" => $tupla->profissao,
                    "estado_civil" => $tupla->estado_civil,
                    "nome_cuidador" => $tupla->nome_cuidador,
                    "telefone_cuidador" => $tupla->telefone_cuidador
                ];
            }

            // Consulta para o total de registros
            $totalSQL = "SELECT COUNT(*) as total FROM paciente WHERE id_instituicao = ?";

            $stmt = $conexao->prepare($totalSQL);
            $stmt->bind_param("i", $this->instituicao);
            $stmt->execute();
            $result = $stmt->get_result();
            $totalRow = $result->fetch_assoc();
            $total = $totalRow['total'];
            $stmt->close();

            return [
                "status" => true,
                "msg" => "Dados encontrados",
                "pacientes" => $vetorPaciente,
                "total" => (int) $total
            ];
        } else {
            error_log("Erro na execução da consulta: " . $stm->error);
            return false;
        }
    }


    // ler por CPF
    public function readCPF()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        if ($conexao->connect_error) {
            die("Falha na conexão: " . $conexao->connect_error);
        }

        $stm = $conexao->prepare("SELECT * FROM paciente WHERE cpf LIKE ? AND id_instituicao = ?");

        $busca = "%" . $this->cpf . "%";
        $stm->bind_param("si", $busca, $this->instituicao);

        if ($stm->execute()) {
            $resultado = $stm->get_result();
            $stm->close();

            if ($resultado->num_rows === 0) {
                return null;
            }

            $pacientes = [];

            while ($linha = $resultado->fetch_object()) {
                $Paciente = new Paciente();
                $Paciente->setcpf($linha->cpf);
                $Paciente->setNome($linha->nome);
                $Paciente->setSexo($linha->sexo);
                $Paciente->setEndereco($linha->endereco);
                $Paciente->setTelefone($linha->telefone);
                $Paciente->setEmail($linha->email);
                $Paciente->setProfissao($linha->profissao);
                $Paciente->setEstadoCivil($linha->estado_civil);
                $Paciente->setNomeCuidador($linha->nome_cuidador);
                $Paciente->setTelefoneCuidador($linha->telefone_cuidador);

                $pacientes[] = $Paciente;
            }

            return $pacientes;
        } else {
            echo "Erro na execução da consulta: " . $stm->error;
            return false;
        }
    }

    public function readCPFdiagnostico()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        if ($conexao->connect_error) {
            die("Falha na conexão: " . $conexao->connect_error);
        }

        $stm = $conexao->prepare("SELECT p.*
                FROM paciente p
                WHERE NOT EXISTS (
                    SELECT *
                    FROM ia_results ia
                    WHERE ia.id_paciente = p.id
                ) AND p.cpf LIKE ? AND p.id_instituicao = ?");

        $busca = "%" . $this->cpf . "%";
        $stm->bind_param("si", $busca, $this->instituicao);

        if ($stm->execute()) {
            $resultado = $stm->get_result();
            $stm->close();

            if ($resultado->num_rows === 0) {
                return null;
            }

            $pacientes = [];

            while ($linha = $resultado->fetch_object()) {
                $Paciente = new Paciente();
                $Paciente->setcpf($linha->cpf);
                $Paciente->setNome($linha->nome);
                $Paciente->setSexo($linha->sexo);
                $Paciente->setEndereco($linha->endereco);
                $Paciente->setTelefone($linha->telefone);
                $Paciente->setEmail($linha->email);
                $Paciente->setProfissao($linha->profissao);
                $Paciente->setEstadoCivil($linha->estado_civil);
                $Paciente->setNomeCuidador($linha->nome_cuidador);
                $Paciente->setTelefoneCuidador($linha->telefone_cuidador);

                $pacientes[] = $Paciente;
            }

            return $pacientes;
        } else {
            echo "Erro na execução da consulta: " . $stm->error;
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


        $sql = "SELECT p.* FROM paciente p WHERE p.nome LIKE ? AND p.id_instituicao = ?";
        $stm = $conexao->prepare($sql);

        $busca = "%" . $this->nome . "%";
        $stm->bind_param("si", $busca, $this->instituicao);

        $stm->execute();
        $resultado = $stm->get_result();
        $stm->close();

        // Se não encontrou pelo nome, tenta pelo email
        if ($resultado->num_rows === 0) {

            $sql = "SELECT p.*
                FROM paciente p
                WHERE NOT EXISTS (
                    SELECT *
                    FROM ia_results ia
                    WHERE ia.id_paciente = p.id
                ) AND p.email LIKE ? AND p.id_instituicao = ?";

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

        $pacientes = [];

        while ($linha = $resultado->fetch_object()) {
            $Paciente = new Paciente();
            $Paciente->setcpf($linha->cpf);
            $Paciente->setNome($linha->nome);
            $Paciente->setSexo($linha->sexo);
            $Paciente->setEndereco($linha->endereco);
            $Paciente->setTelefone($linha->telefone);
            $Paciente->setEmail($linha->email);
            $Paciente->setProfissao($linha->profissao);
            $Paciente->setEstadoCivil($linha->estado_civil);
            $Paciente->setNomeCuidador($linha->nome_cuidador);
            $Paciente->setTelefoneCuidador($linha->telefone_cuidador);

            $pacientes[] = $Paciente;
        }

        return $pacientes;
    }

    public function readStringdiagnostico()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        if ($conexao->connect_error) {
            die("Falha na conexão: " . $conexao->connect_error);
        }

        // Primeiro tenta filtrar pelo nome
        $sql = "
        SELECT p.* 
        FROM paciente p
        LEFT JOIN diagnostico d ON p.id = d.id_paciente
        LEFT JOIN ia_results ia ON p.id = ia.id_paciente
        WHERE p.nome LIKE ? AND d.id_table IS NULL AND ia.id_table IS NULL AND p.id_instituicao = ?
    ";
        $stm = $conexao->prepare($sql);
        $busca = "%" . $this->nome . "%";
        $stm->bind_param("si", $busca, $this->instituicao);
        $stm->execute();
        $resultado = $stm->get_result();
        $stm->close();

        // Se não encontrou pelo nome, tenta pelo email
        if ($resultado->num_rows === 0) {
            $sql = "
            SELECT p.* 
            FROM paciente p
            LEFT JOIN diagnostico d ON p.id = d.id_paciente
            LEFT JOIN ia_results ia ON p.id = ia.id_paciente
            WHERE p.email LIKE ? AND d.id_table IS NULL AND ia.id_table IS NULL AND p.id_instituicao = ?
        ";
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

        $pacientes = [];

        while ($linha = $resultado->fetch_object()) {
            $Paciente = new Paciente();
            $Paciente->setcpf($linha->cpf);
            $Paciente->setNome($linha->nome);
            $Paciente->setSexo($linha->sexo);
            $Paciente->setEndereco($linha->endereco);
            $Paciente->setTelefone($linha->telefone);
            $Paciente->setEmail($linha->email);
            $Paciente->setProfissao($linha->profissao);
            $Paciente->setEstadoCivil($linha->estado_civil);
            $Paciente->setNomeCuidador($linha->nome_cuidador);
            $Paciente->setTelefoneCuidador($linha->telefone_cuidador);

            $pacientes[] = $Paciente;
        }

        return $pacientes;
    }


    // atualizar 

    public function update()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        if ($conexao->connect_error) {
            die("Falha na conexão: " . $conexao->connect_error);
        }

        $sql = "UPDATE Paciente SET nome=?, sexo=?, endereco=?, telefone=?, email=?, profissao=?, estado_civil=?, nome_cuidador=?, telefone_cuidador=?  WHERE cpf = ? AND id_instituicao = ?";
        $stm = $meuBanco->getConexao()->prepare($sql);

        if ($stm === false) {

            return false;
        }

        $stm->bind_param("ssssssssssi",
         $this->nome, $this->sexo, $this->endereco, $this->telefone, $this->email, $this->profissao, $this->estado_civil, $this->nome_cuidador, $this->telefone_cuidador, $this->cpf, $this->instituicao);

        if ($stm->execute()) {
            $stm->close();
            return true;
        } else {
            echo "Erro na execução da consulta: " . $stm->error;
            return false;
        }

    }

    // deletar

    public function delete()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        // Verifica se a conexão foi bem-sucedida
        if ($conexao->connect_error) {
            die("Falha na conexão: " . $conexao->connect_error);
        }

        // Define a consulta SQL para excluir um curso pelo ID
        $SQL = "DELETE FROM paciente WHERE cpf = ? AND id_instituicao = ?;";

        // Prepara a consulta
        if ($prepareSQL = $conexao->prepare($SQL)) {
            // Define o parâmetro da consulta com o ID do curso
            $prepareSQL->bind_param("si", $this->cpf, $this->instituicao);

            // Executa a consulta
            if ($prepareSQL->execute()) {
                // Fecha a consulta preparada
                $prepareSQL->close();
                return true;
            } else {
                // Exibe o erro de execução da consulta
                echo "Erro na execução da consulta: " . $prepareSQL->error;
                return false;
            }
        } else {
            // Exibe o erro na preparação da consulta
            echo "Erro na preparação da consulta: " . $conexao->error;
            return false;
        }
    }

    // GETTERS E SETTERS

    // Getter e Setter para cpf
    public function getCpf()
    {
        return $this->cpf;
    }

    public function setCpf($cpf)
    {
        $this->cpf = $cpf;
    }

    // Getter e Setter para nome
    public function getNome()
    {
        return $this->nome;
    }

    public function setNome($nome)
    {
        $this->nome = $nome;
    }

    // Getter e Setter para sexo
    public function getSexo()
    {
        return $this->sexo;
    }

    public function setSexo($sexo)
    {
        $this->sexo = $sexo;
    }

    // Getter e Setter para endereco
    public function getEndereco()
    {
        return $this->endereco;
    }

    public function setEndereco($endereco)
    {
        $this->endereco = $endereco;
    }

    // Getter e Setter para telefone
    public function getTelefone()
    {
        return $this->telefone;
    }

    public function setTelefone($telefone)
    {
        $this->telefone = $telefone;
    }

    // Getter e Setter para email
    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    // Getter e Setter para profissao
    public function getProfissao()
    {
        return $this->profissao;
    }

    public function setProfissao($profissao)
    {
        $this->profissao = $profissao;
    }

    // Getter e Setter para estado_civil
    public function getEstadoCivil()
    {
        return $this->estado_civil;
    }

    public function setEstadoCivil($estado_civil)
    {
        $this->estado_civil = $estado_civil;
    }

    // Getter e Setter para nome_cuidador
    public function getNomeCuidador()
    {
        return $this->nome_cuidador;
    }

    public function setNomeCuidador($nome_cuidador)
    {
        $this->nome_cuidador = $nome_cuidador;
    }

    // Getter e Setter para telefone_cuidador
    public function getTelefoneCuidador()
    {
        return $this->telefone_cuidador;
    }

    public function setTelefoneCuidador($telefone_cuidador)
    {
        $this->telefone_cuidador = $telefone_cuidador;
    }

    public function getinstituicao()
    {
        return $this->instituicao;
    }

    public function setinstituicao($instituicao)
    {
        $this->instituicao = $instituicao;
    }

}
?>