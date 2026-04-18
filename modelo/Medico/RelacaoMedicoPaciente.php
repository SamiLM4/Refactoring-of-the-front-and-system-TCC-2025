<?php
require_once __DIR__ . "/../Banco.php";


class Relacao
{
    private $cpf_medico;
    private $cpf_paciente;
    private $nome;
    // Método necessário pela interface JsonSerializable para serialização do objeto para JSON
    public function jsonSerialize()
    {
        // Cria um objeto stdClass para armazenar os dados do cargo
        $objetoResposta = new stdClass();
        // Define as propriedades do objeto com os valores das propriedades da classe
        $objetoResposta->cpf_medico = $this->cpf_medico;
        $objetoResposta->cpf_paciente = $this->cpf_paciente;

        // Retorna o objeto para serialização
        return $objetoResposta;
    }

    // Método para converter a instância para um array associativo
    public function toArray()
    {
        return [
            'cpf_medico' => $this->getcpfmedico(),
            'cpf_paciente' => $this->getcpfpaciente()
        ];
    }

    // Cadastrar

    public function cadastrarRelacao()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        if ($conexao->connect_error) {
            die("Falha na conexão: " . $conexao->connect_error);
        }

        $SQL = "INSERT INTO relacao_medico_paciente (id_medico, id_paciente) VALUES ((Select id from medico where cpf = ?), (select id from paciente where cpf = ?)); ";

        if ($prepareSQL = $conexao->prepare($SQL)) {
            $prepareSQL->bind_param("ss", $this->cpf_medico, $this->cpf_paciente);
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


    // Ler tudo

    public function read()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        if ($conexao->connect_error) {
            die("Falha na conexão: " . $conexao->connect_error);
        }

        $stm = $meuBanco->getConexao()->prepare("SELECT * FROM relacao_medico_paciente");
        if ($stm->execute()) {
            $resultado = $stm->get_result();
            $stm->close();

            if ($resultado->num_rows === 0) {
                return null;
            }

            $vetor = array();

            while ($tupla = $resultado->fetch_object()) {
                $vetor[] = [
                    "id_medico" => $tupla->cpf_medico,
                    "id_paciente" => $tupla->cpf_paciente
                ];
            }

            return $vetor;
        } else {
            echo "Erro na execução da consulta: " . $stm->error;
            return false;
        }
    }


    // ler por CPF MÉDICO

    public function readCPFmedico($pagina)
    {
        $itensPorPagina = 5;
        $offset = ($pagina - 1) * $itensPorPagina;

        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        if ($conexao->connect_error) {
            die("Falha na conexão: " . $conexao->connect_error);
        }

        // Contagem total de registros
        $stmCount = $conexao->prepare("
        SELECT COUNT(*) AS total
        FROM 
            relacao_medico_paciente r
        JOIN 
            medico m ON r.id_medico = m.id
        JOIN 
            paciente p ON r.id_paciente = p.id
        WHERE 
            r.id_medico = (SELECT id FROM medico WHERE cpf = ?) ");
        $stmCount->bind_param("s", $this->cpf_medico);
        $stmCount->execute();
        $resultadoCount = $stmCount->get_result();
        $totalRegistros = 0;
        if ($linha = $resultadoCount->fetch_assoc()) {
            $totalRegistros = $linha['total'];
        }
        $stmCount->close();

        // Consulta dos pacientes
        $stm = $conexao->prepare("
        SELECT 
            m.cpf AS cpf_medico, 
            p.cpf AS cpf_paciente, 
            p.nome AS nome_paciente
        FROM 
            relacao_medico_paciente r
        JOIN 
            medico m ON r.id_medico = m.id
        JOIN 
            paciente p ON r.id_paciente = p.id
        WHERE 
            r.id_medico = (SELECT id FROM medico WHERE cpf = ?)
        ORDER BY 
            p.nome ASC
        LIMIT ? OFFSET ?
    ");
        $stm->bind_param("sii", $this->cpf_medico, $itensPorPagina, $offset);
        $pacientes = [];

        if ($stm->execute()) {
            $resultado = $stm->get_result();

            while ($linha = $resultado->fetch_assoc()) {
                $pacientes[] = [
                    "cpf" => $linha['cpf_paciente'],
                    "nome" => $linha['nome_paciente']
                ];
            }

            $stm->close();

            // Retorna os pacientes e o total de registros
            return [
                "pacientes" => $pacientes,
                "total" => $totalRegistros
            ];
        } else {
            echo "Erro na execução da consulta: " . $stm->error;
            return [];
        }
    }


    // ler por CPF PACIENTE

    public function readCPFpaciente()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        if ($conexao->connect_error) {
            die("Falha na conexão: " . $conexao->connect_error);
        }

        $stm = $conexao->prepare("
        SELECT m.cpf AS cpf_medico, p.cpf AS cpf_paciente
        FROM relacao_medico_paciente r
        JOIN medico m ON r.id_medico = m.id
        JOIN paciente p ON r.id_paciente = p.id
        WHERE r.id_paciente = (SELECT id FROM paciente WHERE cpf = ?)
    ");

        $stm->bind_param("s", $this->cpf_paciente);

        $medicos = [];

        if ($stm->execute()) {
            $resultado = $stm->get_result();

            while ($linha = $resultado->fetch_object()) {
                $medico = new Relacao();
                $medico->setcpfmedico($linha->cpf_medico);
                $medico->setcpfpaciente($linha->cpf_paciente);
                $medicos[] = $medico;
            }
            $stm->close();
            return $medicos;  // Mesmo que seja array vazio
        } else {
            echo "Erro na execução da consulta: " . $stm->error;
            return [];  // Retorna array vazio também em caso de erro
        }
    }


    // ler por NOME medico

    public function readNOMEmedico()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        if ($conexao->connect_error) {
            die("Falha na conexão: " . $conexao->connect_error);
        }

        $stm = $conexao->prepare("
                SELECT m.cpf AS cpf_medico, p.cpf AS cpf_paciente
                FROM relacao_medico_paciente r
                JOIN medico m ON r.id_medico = m.id
                JOIN paciente p ON r.id_paciente = p.id
                WHERE m.nome = ?
            ");

        $stm->bind_param("s", $this->nome);

        $medicos = [];

        if ($stm->execute()) {
            $resultado = $stm->get_result();

            if ($resultado->num_rows === 0) {
                $stm->close();
                return null;
            }

            while ($linha = $resultado->fetch_object()) {
                $medico = new Relacao();
                $medico->setcpfmedico($linha->cpf_medico);
                $medico->setcpfpaciente($linha->cpf_paciente);
                $medicos[] = $medico;
            }

            $stm->close();
            return $medicos;
        } else {
            echo "Erro na execução da consulta: " . $stm->error;
            return false;
        }
    }

    // deletar por cpf ambos

    public function deleteCPFambos()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        if ($conexao->connect_error) {
            die("Falha na conexão: " . $conexao->connect_error);
        }

        $SQL = "DELETE FROM relacao_medico_paciente WHERE id_medico = (select id from medico where cpf = ?) and id_paciente = (select id from paciente where cpf = ?);";

        if ($prepareSQL = $conexao->prepare($SQL)) {
            $prepareSQL->bind_param("ss", $this->cpf_medico, $this->cpf_paciente);
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

    // deletar por cpf paciente

    public function deleteCPFpaciente()
    {

        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        if ($conexao->connect_error) {
            die("Falha na conexão: " . $conexao->connect_error);
        }

        $SQL = "DELETE FROM relacao_medico_paciente WHERE id_paciente = (select id from paciente where cpf = ?);";

        if ($prepareSQL = $conexao->prepare($SQL)) {
            $prepareSQL->bind_param("s", $this->cpf_paciente);
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

    // deletar por cpf medico

    public function deleteCPFmedico()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        if ($conexao->connect_error) {
            die("Falha na conexão: " . $conexao->connect_error);
        }

        $SQL = "DELETE FROM relacao_medico_paciente WHERE id_medico = (select id from medico where cpf = ?);";

        if ($prepareSQL = $conexao->prepare($SQL)) {
            $prepareSQL->bind_param("s", $this->cpf_medico);
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
    // UPDATE

    public function updateRelacao($novoCpfMedico)
    {

        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        if ($conexao->connect_error) {
            die("Falha na conexão: " . $conexao->connect_error);
        }

        $SQL = "UPDATE relacao_medico_paciente 
                SET id_medico = (SELECT id FROM medico WHERE cpf = ?)
                WHERE id_paciente = (SELECT id FROM paciente WHERE cpf = ?);";

        if ($prepareSQL = $conexao->prepare($SQL)) {
            $prepareSQL->bind_param("ss", $novoCpfMedico, $this->cpf_paciente);
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
    public function getcpfmedico()
    {
        return $this->cpf_medico;
    }

    public function setcpfmedico($cpf_medico)
    {
        $this->cpf_medico = $cpf_medico;
    }

    // Getter e Setter para crm
    public function getcpfpaciente()
    {
        return $this->cpf_paciente;
    }

    public function setcpfpaciente($cpf_paciente)
    {
        $this->cpf_paciente = $cpf_paciente;
    }

    // Getter e Setter para crm
    public function getnome()
    {
        return $this->nome;
    }

    public function setnome($nome)
    {
        $this->nome = $nome;
    }

}
?>