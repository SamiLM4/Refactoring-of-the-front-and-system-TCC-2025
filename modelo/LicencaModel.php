<?php

require_once __DIR__ . "/BaseModel.php";

class LicencaModel extends BaseModel
{
    protected $table = 'licencas';

    public function createLicense($planoId, $token, $expiraEm)
    {
        $sql = "INSERT INTO {$this->table}
                (token, plano_id, status, usado, expira_em, criado_em)
                VALUES (?, ?, 'ativa', 0, ?, NOW())";

        return $this->query($sql, [$token, $planoId, $expiraEm], "sis");
    }

    public function getByToken($token)
    {
        $sql = "SELECT *
                FROM {$this->table}
                WHERE token = ?
                FOR UPDATE";

        $result = $this->query($sql, [$token], "s");

        return $result->fetch_assoc();
    }

    public function markAsUsed($id, $instituicaoId)
    {
        $sql = "UPDATE {$this->table}
                SET usado = TRUE,
                    instituicao_id = ?
                WHERE id = ?";

        return $this->query($sql, [$instituicaoId, $id], "ii");
    }

    public function getLimiteUsuarios($instituicaoId)
    {
        $sql = "
            SELECT p.limite_usuarios
            FROM {$this->table} l
            INNER JOIN planos p
                ON p.id = l.plano_id
            WHERE l.instituicao_id = ?
              AND l.usado = 1
              AND l.status = 'ativa'
            LIMIT 1
        ";

        $result = $this->query($sql, [$instituicaoId], "i");

        $row = $result->fetch_assoc();

        return $row ? (int) $row['limite_usuarios'] : 0;
    }

    /**
     * Quantidade de usuários ativos da instituição.
     */
    public function getQuantidadeUsuarios($instituicaoId)
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM usuarios
            WHERE instituicao_id = ?
              AND ativo = 1
              AND deleted_at IS NULL
        ";

        $result = $this->query($sql, [$instituicaoId], "i");

        $row = $result->fetch_assoc();

        return (int) $row['total'];
    }

    /**
     * Verifica se ainda há vagas disponíveis no plano.
     * Lança Exception caso tenha atingido o limite.
     */
    public function validarLimiteUsuarios($instituicaoId)
    {
        $limite = $this->getLimiteUsuarios($instituicaoId);

        // 0 significa ilimitado
        if ($limite <= 0) {
            return true;
        }

        $atual = $this->getQuantidadeUsuarios($instituicaoId);

        if ($atual >= $limite) {
            throw new Exception(
                "Limite de usuários atingido para o seu plano ({$limite}). Entre em contato com o suporte para aumentar seu limite."
            );
        }

        return true;
    }
}