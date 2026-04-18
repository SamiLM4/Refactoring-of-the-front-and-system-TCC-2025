<?php

require_once __DIR__ . "/DashboardController.php"; // This will be handled by the autoloader anyway actually, but good for clarity if needed. 
// Wait, index.php has a spl_autoload_register now.

require_once __DIR__ . "/BaseController.php";
require_once __DIR__ . "/../modelo/DashboardModel.php";

class DashboardController extends BaseController {
    private $model;

    public function __construct() {
        $this->model = new DashboardModel();
    }

    public function getStats() {
        $usuario = $GLOBALS['usuario'];
        $stats = $this->model->getStats($usuario['instituicao_id']);
        $this->jsonResponse($stats);
    }

    public function getCharts() {
        $usuario = $GLOBALS['usuario'];
        $data = $this->model->getMonthlyDiagnostics($usuario['instituicao_id']);
        $this->jsonResponse($data);
    }
}
