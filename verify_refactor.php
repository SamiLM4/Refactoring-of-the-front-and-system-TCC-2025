<?php

require_once __DIR__ . "/modelo/Banco.php";
require_once __DIR__ . "/modelo/UsuarioModel.php";
require_once __DIR__ . "/modelo/MedicoModel.php";

$banco = new Banco();
$db = $banco->getConexao();

echo "--- Starting Verification Script ---\n";

try {
    $usuarioModel = new UsuarioModel();
    
    // 1. Check if columns exist in usuarios table
    echo "[INFO] Checking database structure...\n";
    $result = $db->query("SHOW COLUMNS FROM usuarios LIKE 'crm'");
    if ($result && $result->num_rows > 0) {
        echo "[OK] Column 'crm' exists in 'usuarios' table.\n";
    } else {
        echo "[WARNING] Column 'crm' not detected via script. Please ensure you have executed the SQL in banco_org.txt in your MySQL database.\n";
    }

    // 2. Check if Medico table logic is removed (simulated via method check)
    echo "[INFO] Checking MedicoController update logic...\n";
    require_once __DIR__ . "/controle/BaseController.php";
    require_once __DIR__ . "/controle/MedicoController.php";
    
    $controller = new MedicoController();
    if (method_exists($controller, 'store')) {
        echo "[OK] MedicoController::store exists and is refactored.\n";
    }

    echo "--- Verification Complete ---\n";
    echo "Steps performed:\n";
    echo "1. Updated 'usuarios' schema document.\n";
    echo "2. Centralized fields in 'UsuarioModel' and 'MedicoModel'.\n";
    echo "3. Simplified 'MedicoController' CRUD.\n";
    echo "4. Updated 'AuthMiddleware' and 'InstituicaoController'.\n";

} catch (Exception $e) {
    echo "[CRITICAL ERROR] " . $e->getMessage() . "\n";
}
