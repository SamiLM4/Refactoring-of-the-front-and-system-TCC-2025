<?php
require_once __DIR__."/../Banco.php";

class Sintomas
{
    private $id_paciente;
    private $sintomas_iniciais;
    private $sintomas_atuais;
    private $fadiga;
    private $problema_visao;
    private $problema_equilibrio;
    private $problema_coordenacao;
    private $espaticidade;
    private $fraqueza_muscular;
    private $problema_sensibilidade;
    private $problema_bexiga;
    private $problema_intestino ;
    private $problema_cognitivo;
    private $problema_emocional;

    private $cpf;

    // Método necessário pela interface JsonSerializable para serialização do objeto para JSON
    public function jsonSerialize()
    {
        // Cria um objeto stdClass para armazenar os dados do cargo
        $objetoResposta = new stdClass();
        // Define as propriedades do objeto com os valores das propriedades da classe
        $objetoResposta->id_paciente = $this->id_paciente;
        $objetoResposta->cpf = $this->cpf;
        $objetoResposta->sintomas_iniciais = $this->sintomas_iniciais;
        $objetoResposta->sintomas_atuais = $this->sintomas_atuais;
        $objetoResposta->fadiga = $this->fadiga;
        $objetoResposta->problema_visao = $this->problema_visao;
        $objetoResposta->problema_visao = $this->problema_visao;
        $objetoResposta->problema_equilibrio = $this->problema_equilibrio;
        $objetoResposta->espaticidade = $this->espaticidade;
        $objetoResposta->fraqueza_muscular = $this->fraqueza_muscular;
        $objetoResposta->problema_sensibilidade = $this->problema_sensibilidade;
        $objetoResposta->problema_bexiga = $this->problema_bexiga;
        $objetoResposta->problema_intestino = $this->problema_intestino;
        $objetoResposta->problema_cognitivo = $this->problema_cognitivo;
        $objetoResposta->problema_emocional = $this->problema_emocional;
        

        // Retorna o objeto para serialização
        return $objetoResposta;
    }



    // Método para converter a instância para um array associativo
    public function toArray()
    {
        return [
            'id_paciente' => $this->getIdPaciente(),
            'cpf' => $this->getCpf(),
            'sintomas_iniciais' => $this->getSintomasIniciais(),
            'sintomas_atuais' => $this->getSintomasAtuais(),
            'fadiga' => $this->getFadiga(),
            'problema_visao' => $this->getProblemaVisao(),
            'problema_equilibrio' => $this->getProblemaEquilibrio(),
            'problema_coordenacao' => $this->getProblemaCoordenacao(),
            'espaticidade' => $this->getEspaticidade(),
            'fraqueza_muscular' => $this->getFraquezaMuscular(),
            'problema_sensibilidade' => $this->getProblemaSensibilidade(),
            'problema_bexiga' => $this->getProblemaBexiga(),
            'problema_intestino' => $this->getProblemaIntestino(),
            'problema_cognitivo' => $this->getProblemaCognitivo(),
            'problema_emocional' => $this->getProblemaEmocional()

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

    // Preparar insert com valores reais
    $stm = $conexao->prepare("INSERT INTO sintomas (
        id_paciente, sintomas_iniciais, sintomas_atuais, fadiga,
        problema_visao, problema_equilibrio, problema_coordenacao, espaticidade, fraqueza_muscular,
        problema_sensibilidade, problema_bexiga, problema_intestino, problema_cognitivo, problema_emocional
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stm->bind_param(
        "ississiiiisiss",
        $id_paciente,
        $this->sintomas_iniciais,
        $this->sintomas_atuais,
        $this->fadiga,
        $this->problema_visao,
        $this->problema_equilibrio,
        $this->problema_coordenacao,
        $this->espaticidade,
        $this->fraqueza_muscular,
        $this->problema_sensibilidade,
        $this->problema_bexiga,
        $this->problema_intestino,
        $this->problema_cognitivo,
        $this->problema_emocional
    );

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

        $stm = $meuBanco->getConexao()->prepare("SELECT * FROM sintomas WHERE id_paciente = (select id from paciente where cpf = ?)");
        $stm->bind_param("s", $this->cpf);

        if ($stm->execute()) {

            $resultado = $stm->get_result();
            $stm->close();

            if ($resultado->num_rows === 0) {
                return null; // Se nenhum curso for encontrado, retorna null
            }

            $linha = $resultado->fetch_object();
            $Sintomas = new Sintomas(); // Instancia um novo objeto Curso

            // Define as propriedades do curso com os valores do banco de dados
            $Sintomas->setIdPaciente($linha->id_paciente);
            $Sintomas->setCpf($this->cpf);
            $Sintomas->setSintomasIniciais($linha->sintomas_iniciais);
            $Sintomas->setSintomasAtuais($linha->sintomas_atuais);
            $Sintomas->setFadiga($linha->fadiga);
            $Sintomas->setProblemaVisao($linha->problema_visao);
            $Sintomas->setProblemaEquilibrio($linha->problema_equilibrio);
            $Sintomas->setProblemaCoordenacao($linha->problema_coordenacao);
            $Sintomas->setEspaticidade($linha->espaticidade);
            $Sintomas->setFraquezaMuscular($linha->fraqueza_muscular);
            $Sintomas->setProblemaSensibilidade($linha->problema_sensibilidade);
            $Sintomas->setProblemaBexiga($linha->problema_bexiga);
            $Sintomas->setProblemaIntestino($linha->problema_intestino);
            $Sintomas->setProblemaCognitivo($linha->problema_cognitivo);
            $Sintomas->setProblemaEmocional($linha->problema_emocional);

            return $Sintomas; // Retorna o objeto Curso encontrado
        } else {
             echo "Erro na execução da consulta: " . $stm->error;
            return false;
        }
    }

    // atualizar

    public function update()
    {
        $meuBanco = new Banco();
        $sql = "UPDATE sintomas 
                SET 
                    sintomas_iniciais = ?,
                    sintomas_atuais = ?,
                    fadiga = ?,
                    problema_visao = ?,
                    problema_equilibrio = ?,
                    problema_coordenacao = ?,
                    espaticidade = ?,
                    fraqueza_muscular = ?,
                    problema_sensibilidade = ?,
                    problema_bexiga = ?,
                    problema_intestino = ?,
                    problema_cognitivo = ?,
                    problema_emocional = ?
                WHERE id_paciente = (
                    SELECT id FROM paciente WHERE cpf = ?
                );
";
        $stm = $meuBanco->getConexao()->prepare($sql);

        if ($stm === false) {
            // Handle error if the statement couldn't be prepared
            return false;
        }

        // Tipos de parâmetros: "s" para strings, "d" para doubles, "i" para inteiros
        $stm->bind_param("ssisiiiisiisss", $this->sintomas_iniciais, $this->sintomas_atuais, $this->fadiga,
                                            $this->problema_visao, $this->problema_equilibrio, $this->problema_coordenacao, $this->espaticidade, $this->fraqueza_muscular,
                                            $this->problema_sensibilidade, $this->problema_bexiga, $this->problema_intestino, $this->problema_cognitivo, $this->problema_emocional,
                                            $this->cpf);


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
        $SQL = "DELETE FROM sintomas WHERE id_paciente = (select id from paciente where cpf = ?);";

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
    
        // Getters
        public function getIdPaciente(): int {
            return $this->id_paciente;
        }
    
        public function getSintomasIniciais(): string {
            return $this->sintomas_iniciais;
        }
    
        public function getSintomasAtuais(): string {
            return $this->sintomas_atuais;
        }
    
        public function getFadiga(): bool {
            return $this->fadiga;
        }
    
        public function getProblemaVisao(): string {
            return $this->problema_visao;
        }
    
        public function getProblemaEquilibrio(): bool {
            return $this->problema_equilibrio;
        }
    
        public function getProblemaCoordenacao(): bool {
            return $this->problema_coordenacao;
        }
    
        public function getEspaticidade(): bool {
            return $this->espaticidade;
        }
    
        public function getFraquezaMuscular(): bool {
            return $this->fraqueza_muscular;
        }
    
        public function getProblemaSensibilidade(): string {
            return $this->problema_sensibilidade;
        }
    
        public function getProblemaBexiga(): bool {
            return $this->problema_bexiga;
        }
    
        public function getProblemaIntestino(): bool {
            return $this->problema_intestino;
        }
    
        public function getProblemaCognitivo(): string {
            return $this->problema_cognitivo;
        }
    
        public function getProblemaEmocional(): string {
            return $this->problema_emocional;
        }
    
        // Setters
        public function setIdPaciente(int $id): void {
            $this->id_paciente = $id;
        }
    
        public function setSintomasIniciais(string $sintomas): void {
            $this->sintomas_iniciais = $sintomas;
        }
    
        public function setSintomasAtuais(string $sintomas): void {
            $this->sintomas_atuais = $sintomas;
        }
    
        public function setFadiga(bool $fadiga): void {
            $this->fadiga = $fadiga;
        }
    
        public function setProblemaVisao(string $visao): void {
            $this->problema_visao = $visao;
        }
    
        public function setProblemaEquilibrio(bool $equilibrio): void {
            $this->problema_equilibrio = $equilibrio;
        }
    
        public function setProblemaCoordenacao(bool $coordenacao): void {
            $this->problema_coordenacao = $coordenacao;
        }
    
        public function setEspaticidade(bool $espaticidade): void {
            $this->espaticidade = $espaticidade;
        }
    
        public function setFraquezaMuscular(bool $fraqueza): void {
            $this->fraqueza_muscular = $fraqueza;
        }
    
        public function setProblemaSensibilidade(string $sensibilidade): void {
            $this->problema_sensibilidade = $sensibilidade;
        }
    
        public function setProblemaBexiga(bool $bexiga): void {
            $this->problema_bexiga = $bexiga;
        }
    
        public function setProblemaIntestino(bool $intestino): void {
            $this->problema_intestino = $intestino;
        }
    
        public function setProblemaCognitivo(string $cognitivo): void {
            $this->problema_cognitivo = $cognitivo;
        }
    
        public function setProblemaEmocional(string $emocional): void {
            $this->problema_emocional = $emocional;
        }

        // 

        public function getCpf(): string {
            return $this->cpf;
        }

        public function setCpf(string $cpf): void {
            $this->cpf = $cpf;
        }

    
}
?>