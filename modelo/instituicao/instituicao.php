<?php
require_once __DIR__ . "/../Banco.php";

class Instituicao
{
    private $id;
    private $nome;
    private $CEP;
    private $logradouro;
    private $cidade;
    private $bairro;
    private $cnpj;
    private $tipo;
    private $telefone;
    private $email;
    private $site;
    private $atividade;
    private $nome_responsavel;
    private $telefone_responsavel;

    // Retorna em formato JSON
    public function jsonSerialize()
    {
        $resposta = new stdClass();
        $resposta->id = $this->id;
        $resposta->nome = $this->nome;
        $resposta->cnpj = $this->cnpj;
        $resposta->tipo = $this->tipo;
        $resposta->atividade = $this->atividade;
        return $resposta;
    }

    // Inserir nova instituição
    // Inserir nova instituição
    public function cadastrar()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        if ($conexao->connect_error) {
            return [
                "status" => false,
                "msg" => "Falha na conexão com o banco de dados."
            ];
        }

        $SQL = "INSERT INTO instituicao (nome, CEP, logradouro, cidade, bairro, cnpj, tipo, telefone, email, site, atividade, nome_responsavel, telefone_responsavel)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";

        if ($stmt = $conexao->prepare($SQL)) {
            $stmt->bind_param(
                "sssssssssssss",
                $this->nome,
                $this->CEP,
                $this->logradouro,
                $this->cidade,
                $this->bairro,
                $this->cnpj,
                $this->tipo,
                $this->telefone,
                $this->email,
                $this->site,
                $this->atividade,
                $this->nome_responsavel,
                $this->telefone_responsavel
            );

            if ($stmt->execute()) {
                $idInstituicao = $conexao->insert_id;
                $stmt->close();

                // 🔹 Cadastro automático de ADM
                require_once __DIR__ . "/../adm/administrador.php";
                $adm = new Adm();
                $adm->setinstituicao($idInstituicao);
                $adm->setnome("Administrador da " . $this->nome);
                $adm->setemail($this->email ?? "admin@" . preg_replace('/\s+/', '', strtolower($this->nome)) . ".com");
                $adm->setsenha("1234");
                $admCadastrado = $adm->cadastrarAdm();

                return [
                    "status" => $admCadastrado,
                    "msg" => $admCadastrado
                        ? "Instituição cadastrada com sucesso e administrador criado automaticamente."
                        : "Instituição cadastrada, mas houve erro ao criar o administrador padrão.",
                    "id_instituicao" => $idInstituicao,
                    "Login do ADM" => $adm->getemail(),
                    "Senha do ADM" => $adm->getsenha()
                ];
            } else {
                return [
                    "status" => false,
                    "msg" => "Erro ao executar a query: " . $stmt->error
                ];
            }
        } else {
            return [
                "status" => false,
                "msg" => "Erro ao preparar a query: " . $conexao->error
            ];
        }
    }



    // Ler todas as instituições
    public function read()
    {
        $meuBanco = new Banco();
        $conexao = $meuBanco->getConexao();

        $SQL = "SELECT * FROM instituicao ORDER BY nome ASC;";
        $resultado = $conexao->query($SQL);

        $instituicoes = [];
        while ($linha = $resultado->fetch_object()) {
            $instituicoes[] = [
                "id" => $linha->id,
                "nome" => $linha->nome,
                "cnpj" => $linha->cnpj,
                "tipo" => $linha->tipo,
                "atividade" => $linha->atividade
            ];
        }

        return [
            "status" => true,
            "msg" => "Instituições encontradas.",
            "instituicoes" => $instituicoes
        ];
    }

    // Getters e Setters
    public function setNome($nome)
    {
        $this->nome = trim($nome);
    }
    public function getNome()
    {
        return $this->nome;
    }

    public function setCEP($CEP)
    {
        $this->CEP = trim($CEP);
    }
    public function getCEP()
    {
        return $this->CEP;
    }

    public function setLogradouro($logradouro)
    {
        $this->logradouro = trim($logradouro);
    }
    public function getLogradouro()
    {
        return $this->logradouro;
    }

    public function setCidade($cidade)
    {
        $this->cidade = trim($cidade);
    }
    public function getCidade()
    {
        return $this->cidade;
    }

    public function setBairro($bairro)
    {
        $this->bairro = trim($bairro);
    }
    public function getBairro()
    {
        return $this->bairro;
    }

    public function setCnpj($cnpj)
    {
        $this->cnpj = trim($cnpj);
    }
    public function getCnpj()
    {
        return $this->cnpj;
    }

    public function setTipo($tipo)
    {
        $tipo = trim($tipo);
        $tiposValidos = ['publico', 'privado', 'filantropico'];
        if (!in_array($tipo, $tiposValidos)) {
            throw new Exception("Tipo de instituição inválido. Use: público, privado ou filantrópico.");
        }
        $this->tipo = $tipo;

    }
    public function getTipo()
    {
        return $this->tipo;
    }

    public function setTelefone($telefone)
    {
        $this->telefone = trim($telefone);
    }
    public function getTelefone()
    {
        return $this->telefone;
    }

    public function setEmail($email)
    {
        $this->email = trim($email);
    }
    public function getEmail()
    {
        return $this->email;
    }

    public function setSite($site)
    {
        $this->site = trim($site);
    }
    public function getSite()
    {
        return $this->site;
    }

    public function setAtividade($atividade)
    {
        $this->atividade = $atividade;
    }
    public function getAtividade()
    {
        return $this->atividade;
    }

    public function setNomeResponsavel($nome_responsavel)
    {
        $this->nome_responsavel = trim($nome_responsavel);
    }
    public function getNomeResponsavel()
    {
        return $this->nome_responsavel;
    }

    public function setTelefoneResponsavel($telefone_responsavel)
    {
        $this->telefone_responsavel = trim($telefone_responsavel);
    }
    public function getTelefoneResponsavel()
    {
        return $this->telefone_responsavel;
    }
}
?>