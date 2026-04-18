<?php
require_once __DIR__ . "/../Banco.php";

class HistoricoMedico
{
    private $id_paciente;
    private $medicamento_em_uso;
    private $tratamentos_anteriores_em;
    private $alergias;
    private $historico_outras_doencas;
    private $historico_familiar;
    private $cpf;

    // Método necessário pela interface JsonSerializable para serialização do objeto para JSON
    public function jsonSerialize()
    {
        // Cria um objeto stdClass para armazenar os dados do cargo
        $objetoResposta = new stdClass();
        // Define as propriedades do objeto com os valores das propriedades da classe
        $objetoResposta->id_paciente = $this->id_paciente;
        $objetoResposta->medicamento_em_uso = $this->medicamento_em_uso;
        $objetoResposta->tratamentos_anteriores_em = $this->tratamentos_anteriores_em;
        $objetoResposta->alergias = $this->alergias;
        $objetoResposta->historico_outras_doencas = $this->historico_outras_doencas;
        $objetoResposta->historico_familiar = $this->historico_familiar;


        // Retorna o objeto para serialização
        return $objetoResposta;
    }



    // Método para converter a instância para um array associativo
    public function toArray()
    {
        return [
            'id_paciente' => $this->getIdPaciente(),
            'medicamento_em_uso' => $this->getMedicamentoEmUso(),
            'tratamentos_anteriores' => $this->getTratamentosAnteriores(),
            'alergias' => $this->getAlergias(),
            'historico_outras_doencas' => $this->getHistoricoOutrasDoencas(),
            'historico_familiar' => $this->getHistoricoFamiliar()

        ];
    }

    // cadastrar diagnostico

    public function cadastrar()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        // Buscar id_paciente pelo CPF
        $query = $conexao->prepare("SELECT id FROM paciente WHERE cpf = ?");
        $query->bind_param("s", $this->cpf);
        $query->execute();
        $resultado = $query->get_result();

        if ($resultado->num_rows === 0) {
            return false; // CPF não encontrado
        }

        $row = $resultado->fetch_assoc();
        $id_paciente = $row['id'];
        $query->close();

        $stm = $conexao->prepare("INSERT INTO historico_medico (id_paciente, medicamento_em_uso, tratamentos_anteriores_em, alergias, historico_outras_doencas, historico_familiar) 
                                    VALUES (?, ?, ?, ?, ?, ?);

                                ");

        $stm->bind_param("isssss", $id_paciente, $this->medicamento_em_uso, $this->tratamentos_anteriores_em, $this->alergias, $this->historico_outras_doencas, $this->historico_familiar);

        if ($stm->execute()) {
            $stm->close();
            return true;
        } else {
            return false;
        }
    }

    // ler tudo - Não necessário


    /*
    public function readPage($pagina)
    {
        $itempaginas = 10;
        $incio = ($pagina - 1) * $itempaginas;
        // Obtém a conexão com o banco de dados
        $meuBanco = new Banco();
        // Define a consulta SQL para selecionar todos os cargos ordenados por nome
        $stm = $meuBanco->getConexao()->prepare("SELECT * FROM Curso limit ?,?");
        $stm->bind_param("ii", $incio, $itempaginas);
        $stm->execute();
        $executou = $stm->get_result();

        if (!$executou) {
            throw new Exception("Erro ao executar a consulta SQL");
        }
        $executou = $executou->fetch_all(MYSQLI_ASSOC);
        return $executou;
    }
    */

    // ler por cpf

    public function readCPF()
    {
        $meuBanco = new Banco();

        $conexao = $meuBanco->getConexao();

        if ($conexao->connect_error) {
            die("Falha na conexão: " . $conexao->connect_error);
        }

        $stm = $meuBanco->getConexao()->prepare("SELECT * FROM historico_medico WHERE id_paciente = (select id from paciente where cpf = ?)");
        $stm->bind_param("s", $this->cpf);

        if ($stm->execute()) {

            $resultado = $stm->get_result();
            $stm->close();

            if ($resultado->num_rows === 0) {
                return null; // Se nenhum curso for encontrado, retorna null
            }

            $linha = $resultado->fetch_object();
            $historicoMedico = new HistoricoMedico(); // Instancia um novo objeto Curso

            // Define as propriedades do curso com os valores do banco de dados
            $historicoMedico->setIdPaciente($linha->id_paciente);
            $historicoMedico->setCpf($this->cpf);
            $historicoMedico->setMedicamentoEmUso($linha->medicamento_em_uso);
            $historicoMedico->setTratamentosAnteriores($linha->tratamentos_anteriores_em);
            $historicoMedico->setAlergias($linha->alergias);
            $historicoMedico->setHistoricoOutrasDoencas($linha->historico_outras_doencas);
            $historicoMedico->setHistoricoFamiliar($linha->historico_familiar);

            return $historicoMedico; // Retorna o objeto Curso encontrado
        } else {
            echo "Erro na execução da consulta: " . $stm->error;
            return false;
        }
    }

    // atualizar

    public function update()
    {
        $meuBanco = new Banco();
        $sql = "UPDATE historico_medico SET medicamento_em_uso = ?, tratamentos_anteriores_em = ?, alergias = ?,
                historico_outras_doencas = ?, historico_familiar=?
                 WHERE id_paciente = (select id from paciente where cpf = ?)";
        $stm = $meuBanco->getConexao()->prepare($sql);

        if ($stm === false) {
            // Handle error if the statement couldn't be prepared
            return false;
        }

        // Tipos de parâmetros: "s" para strings, "d" para doubles, "i" para inteiros
        $stm->bind_param(
            "ssssss",
            $this->medicamento_em_uso,
            $this->tratamentos_anteriores_em,
            $this->alergias,
            $this->historico_outras_doencas,
            $this->historico_familiar,
            $this->cpf
        );

        if ($stm->execute()) {
            $stm->close();
            return true;
        } else {
            return false;
        }
        ;
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
        $SQL = "DELETE FROM historico_medico WHERE id_paciente = (select id from paciente where cpf = ?);";

        // Prepara a consulta
        if ($prepareSQL = $conexao->prepare($SQL)) {
            // Define o parâmetro da consulta com o ID do curso
            $prepareSQL->bind_param("s", $this->cpf);

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

    // getters e setters

    // Getters
    public function getIdPaciente()
    {
        return $this->id_paciente;
    }

    public function getMedicamentoEmUso()
    {
        return $this->medicamento_em_uso;
    }

    public function getTratamentosAnteriores()
    {
        return $this->tratamentos_anteriores_em;
    }

    public function getAlergias()
    {
        return $this->alergias;
    }

    public function getHistoricoOutrasDoencas()
    {
        return $this->historico_outras_doencas;
    }

    public function getHistoricoFamiliar()
    {
        return $this->historico_familiar;
    }

    public function getCpf()
    {
        return $this->cpf;
    }
    // Setters
    public function setIdPaciente($id_paciente)
    {
        $this->id_paciente = $id_paciente;
    }

    public function setMedicamentoEmUso($medicamento_em_uso)
    {
        $this->medicamento_em_uso = $medicamento_em_uso;
    }

    public function setTratamentosAnteriores($tratamentos_anteriores_em)
    {
        $this->tratamentos_anteriores_em = $tratamentos_anteriores_em;
    }

    public function setAlergias($alergias)
    {
        $this->alergias = $alergias;
    }

    public function setHistoricoOutrasDoencas($historico_outras_doencas)
    {
        $this->historico_outras_doencas = $historico_outras_doencas;
    }

    public function setHistoricoFamiliar($historico_familiar)
    {
        $this->historico_familiar = $historico_familiar;
    }
    public function setCpf($cpf)
    {
        $this->cpf = $cpf;
    }

}
?>