<?php
require_once __DIR__."/../Banco.php";

class Diagnostico
{
    private $id_paciente;
    private $data_diagnostico;
    private $tipo_em;
    private $surtos;
    private $cpf;

    // Método necessário pela interface JsonSerializable para serialização do objeto para JSON
    public function jsonSerialize()
    {
        // Cria um objeto stdClass para armazenar os dados do cargo
        $objetoResposta = new stdClass();
        // Define as propriedades do objeto com os valores das propriedades da classe
        $objetoResposta->id_paciente = $this->id_paciente;
        $objetoResposta->cpf = $this->cpf;
        $objetoResposta->data_diagnostico = $this->data_diagnostico;
        $objetoResposta->tipo_em = $this->tipo_em;
        $objetoResposta->surtos = $this->surtos;
        

        // Retorna o objeto para serialização
        return $objetoResposta;
    }



    // Método para converter a instância para um array associativo
    public function toArray()
    {
        return [
            'id do paciente' => $this->getIdPaciente(),
            'cpf' => $this->getCpf(),
            'data_diagnostico' => $this->getDataDiagnostico(),
            'tipo_em' => $this->getTipoEm(),
            'surtos' => $this->getSurtos()

        ];
    }

    // cadastrar diagnostico

    public function cadastrar()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        $stm = $conexao->prepare("INSERT INTO diagnostico (id_paciente, data_diagnostico, tipo_em, surtos)
                                    VALUES (
                                        (SELECT id FROM paciente WHERE cpf = ?),
                                        ?,
                                        ?,
                                        ?
                                    );
                                ");

        $stm->bind_param("ssss", $this->cpf, $this->data_diagnostico, $this->tipo_em, $this->surtos);

        if($stm->execute()) {
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

        $stm = $meuBanco->getConexao()->prepare("SELECT * FROM diagnostico WHERE id_paciente = (select id from paciente where cpf = ?)");
        $stm->bind_param("s", $this->cpf);

        if ($stm->execute()) {

            $resultado = $stm->get_result();
            $stm->close();

            if ($resultado->num_rows === 0) {
                return null; // Se nenhum curso for encontrado, retorna null
            }

            $linha = $resultado->fetch_object();
            $diagnostico = new Diagnostico(); // Instancia um novo objeto Curso

            // Define as propriedades do curso com os valores do banco de dados
            $diagnostico->setIdPaciente($linha->id_paciente);
            $diagnostico->setCpf($this->cpf);
            $diagnostico->setDataDiagnostico($linha->data_diagnostico);
            $diagnostico->setTipoEm($linha->tipo_em);
            $diagnostico->setSurtos($linha->surtos);

            return $diagnostico; // Retorna o objeto Curso encontrado
        } else {
             echo "Erro na execução da consulta: " . $stm->error;
            return false;
        }
    }

    // atualizar

    public function update()
    {
        $meuBanco = new Banco();
        $sql = "UPDATE diagnostico SET data_diagnostico = ?, tipo_em = ?, surtos = ? WHERE id_paciente = (select id from paciente where cpf = ?)";
        $stm = $meuBanco->getConexao()->prepare($sql);

        if ($stm === false) {
            // Handle error if the statement couldn't be prepared
            return false;
        }

        // Tipos de parâmetros: "s" para strings, "d" para doubles, "i" para inteiros
        $stm->bind_param("ssss", $this->data_diagnostico, $this->tipo_em, $this->surtos, $this->cpf);

        if($stm->execute()){
            $stm->close();
            return true;
        } else {
            return false;
        };
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
        $SQL = "DELETE FROM diagnostico WHERE id_paciente = (select id from paciente where cpf = ?);";

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

    // Getter e Setter para id_paciente
    public function getIdPaciente()
    {
        return $this->id_paciente;
    }

    public function setIdPaciente($id_paciente)
    {
        $this->id_paciente = $id_paciente;
    }

    // Getter e Setter para data_diagnostico
    public function getDataDiagnostico()
    {
        return $this->data_diagnostico;
    }

    public function setDataDiagnostico($data_diagnostico)
    {
        $this->data_diagnostico = $data_diagnostico;
    }

    // Getter e Setter para tipo_em
    public function getTipoEm()
    {
        return $this->tipo_em;
    }

    public function setTipoEm($tipo_em)
    {
        $this->tipo_em = $tipo_em;
    }

    // Getter e Setter para surtos
    public function getSurtos()
    {
        return $this->surtos;
    }

    public function setSurtos($surtos)
    {
        $this->surtos = $surtos;
    }

    //Getter e Setter para cpf
        public function getCpf()
    {
        return $this->cpf;
    }

    public function setCpf($cpf)
    {
        $this->cpf = $cpf;
    }


}
?>