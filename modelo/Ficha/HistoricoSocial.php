<?php
require_once __DIR__ . "/../Banco.php";

class HistoricoSocial
{
    private $id_paciente;
    private $tabagismo;
    private $alcool;
    private $atividade_fisica;
    private $suporte_social;
    private $impacto_profissional_social;
    private $cpf;

    // Método necessário pela interface JsonSerializable para serialização do objeto para JSON
    // Método para serializar em JSON
    public function jsonSerialize()
    {
        $objetoResposta = new stdClass();
        $objetoResposta->id_paciente = $this->id_paciente;
        $objetoResposta->tabagismo = $this->tabagismo;
        $objetoResposta->alcool = $this->alcool;
        $objetoResposta->atividade_fisica = $this->atividade_fisica;
        $objetoResposta->suporte_social = $this->suporte_social;
        $objetoResposta->impacto_profissional_social = $this->impacto_profissional_social;

        return $objetoResposta;
    }

    // Método para converter para array
    public function toArray()
    {
        return [
            'id_paciente' => $this->getIdPaciente(),
            'tabagismo' => $this->getTabagismo(),
            'alcool' => $this->getAlcool(),
            'atividade_fisica' => $this->getAtividadeFisica(),
            'suporte_social' => $this->getSuporteSocial(),
            'impacto_profissional_social' => $this->getImpactoProfissionalSocial()
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

        $stm = $conexao->prepare("INSERT INTO historico_social (id_paciente, tabagismo, alcool, atividade_fisica, suporte_social, impacto_profissional_social) 
                                    VALUES (?, ?, ?, ?, ?, ?);
                                ");

        $stm->bind_param("isssss", $id_paciente, $this->tabagismo, $this->alcool, $this->atividade_fisica, $this->suporte_social, $this->impacto_profissional_social);

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

        $stm = $meuBanco->getConexao()->prepare("SELECT * FROM historico_social WHERE id_paciente = (select id from paciente where cpf = ?)");
        $stm->bind_param("s", $this->cpf);

        if ($stm->execute()) {

            $resultado = $stm->get_result();
            $stm->close();

            if ($resultado->num_rows === 0) {
                return null; // Se nenhum curso for encontrado, retorna null
            }

            $linha = $resultado->fetch_object();
            $historicoSocial = new HistoricoSocial(); // Instancia um novo objeto Curso

            // Define as propriedades do curso com os valores do banco de dados
            $historicoSocial->setIdPaciente($linha->id_paciente);
            $historicoSocial->setCpf($this->cpf);
            $historicoSocial->settabagismo($linha->tabagismo);
            $historicoSocial->setAtividadeFisica($linha->atividade_fisica);
            $historicoSocial->setalcool($linha->alcool);
            $historicoSocial->setSuporteSocial($linha->suporte_social);
            $historicoSocial->setImpactoProfissionalSocial($linha->impacto_profissional_social);

            return $historicoSocial; // Retorna o objeto Curso encontrado
        } else {
            echo "Erro na execução da consulta: " . $stm->error;
            return false;
        }
    }

    // atualizar

    public function update()
    {
        $meuBanco = new Banco();
        $sql = "UPDATE historico_social SET tabagismo = ?, alcool = ?, atividade_fisica = ?,
                suporte_social = ?, impacto_profissional_social=?
                 WHERE id_paciente = (select id from paciente where cpf = ?)";
        $stm = $meuBanco->getConexao()->prepare($sql);

        if ($stm === false) {
            // Handle error if the statement couldn't be prepared
            return false;
        }

        // Tipos de parâmetros: "s" para strings, "d" para doubles, "i" para inteiros
        $stm->bind_param("ssssss", $this->tabagismo, $this->alcool, $this->atividade_fisica, $this->suporte_social, $this->impacto_profissional_social, $this->cpf);

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
        $SQL = "DELETE FROM historico_social WHERE id_paciente = (select id from paciente where cpf = ?);";

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

    public function getTabagismo()
    {
        return $this->tabagismo;
    }

    public function getAlcool()
    {
        return $this->alcool;
    }

    public function getAtividadeFisica()
    {
        return $this->atividade_fisica;
    }

    public function getSuporteSocial()
    {
        return $this->suporte_social;
    }

    public function getImpactoProfissionalSocial()
    {
        return $this->impacto_profissional_social;
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

    public function setTabagismo($tabagismo)
    {
        $this->tabagismo = $tabagismo;
    }

    public function setAlcool($alcool)
    {
        $this->alcool = $alcool;
    }

    public function setAtividadeFisica($atividade_fisica)
    {
        $this->atividade_fisica = $atividade_fisica;
    }

    public function setSuporteSocial($suporte_social)
    {
        $this->suporte_social = $suporte_social;
    }

    public function setImpactoProfissionalSocial($impacto_profissional_social)
    {
        $this->impacto_profissional_social = $impacto_profissional_social;
    }

    public function setCpf($cpf)
    {
        $this->cpf = $cpf;
    }

}
?>