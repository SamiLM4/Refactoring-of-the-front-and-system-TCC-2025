<?php

require_once __DIR__ . "/BaseModel.php";

require_once __DIR__ . "/UsuarioModel.php";

class DashboardModel extends BaseModel {
    
    public function getStats($instituicaoId) {
        $usuarioModel = new UsuarioModel();
        $usuarioModel->setDb($this->db);
        $usuarioModel->ensureDefaultRoles($instituicaoId);

        $stats = [];

        // Count Pacientes
        $sql = "SELECT COUNT(*) as total FROM pacientes WHERE instituicao_id = ? AND deleted_at IS NULL";
        $result = $this->query($sql, [$instituicaoId], "i");
        $stats['pacientes'] = $result->fetch_assoc()['total'];

        // Count Médicos
        $sql = "
            SELECT COUNT(DISTINCT u.id) as total 
            FROM usuarios u
            JOIN usuarios_papeis up ON up.usuario_id = u.id
            JOIN papeis p ON p.id = up.papel_id
            WHERE u.instituicao_id = ? 
            AND p.nome = 'MEDICO' 
            AND p.is_delete = 0
            AND u.deleted_at IS NULL";
        $result = $this->query($sql, [$instituicaoId], "i");
        $stats['medicos'] = $result->fetch_assoc()['total'];

        // Count IA Diagnostics
        $sql = "SELECT COUNT(*) as total FROM ia_results WHERE instituicao_id = ?";
        $result = $this->query($sql, [$instituicaoId], "i");
        $stats['ia_diagnosticos'] = $result->fetch_assoc()['total'];

        // Count Alerts (Simulated/Placeholder for now, or based on specific criteria if known)
        // For now, let's say "Alertas" are diagnostics from today
        $sql = "SELECT COUNT(*) as total FROM ia_results WHERE instituicao_id = ? AND DATE(data_diagnostico) = CURDATE()";
        $result = $this->query($sql, [$instituicaoId], "i");
        $stats['alertas'] = $result->fetch_assoc()['total'];

        // Get roles statistics (dynamic carousel implementation)
        $sql = "
            SELECT p.id, p.nome, COUNT(DISTINCT u.id) as total 
            FROM papeis p
            LEFT JOIN usuarios_papeis up ON p.id = up.papel_id
            LEFT JOIN usuarios u ON up.usuario_id = u.id AND u.instituicao_id = ? AND u.deleted_at IS NULL
            WHERE p.instituicao_id = ? AND p.is_delete = 0
            GROUP BY p.id, p.nome
            ORDER BY p.id ASC
        ";
        $result = $this->query($sql, [$instituicaoId, $instituicaoId], "ii");
        $roles_stats = [];
        while ($row = $result->fetch_assoc()) {
            $roles_stats[] = $row;
        }
        $stats['roles_stats'] = $roles_stats;

        return $stats;
    }

    public function getMonthlyDiagnostics($instituicaoId) {
        $sql = "
            SELECT 
                MONTH(data_diagnostico) as mes, 
                COUNT(*) as total 
            FROM ia_results 
            WHERE instituicao_id = ? 
            AND data_diagnostico >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY MONTH(data_diagnostico)
            ORDER BY data_diagnostico ASC
        ";
        $result = $this->query($sql, [$instituicaoId], "i");
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }
}
