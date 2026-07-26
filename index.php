<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 86400");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . "/vendor/autoload.php";
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();


define('BASE_PATH', __DIR__);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Simple Autoloader for MVC
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/controle/',
        __DIR__ . '/modelo/'
    ];
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

require_once("modelo/Router.php");

function proteger(array $permissoes, $handler, bool $exigirLicenca = false)
{
    return function (...$params) use ($permissoes, $handler, $exigirLicenca) {
        require_once __DIR__ . "/middleware/authMiddleware.php";
        $usuario = authMiddleware($permissoes, $exigirLicenca);
        $GLOBALS['usuario'] = $usuario;

        // VERIFICAR ACESSO DO PACIENTE (SOMENTE SEUS PRÓPRIOS DADOS)
        $isPaciente = false;
        foreach ($usuario['papeis'] as $p) {
            if (strtoupper($p['nome']) === 'PACIENTE') {
                $isPaciente = true;
                break;
            }
        }

        if ($isPaciente && empty($usuario['admin_owner'])) {
            $requestUri = $_SERVER['REQUEST_URI'];
            // Check if route accesses a specific paciente_id endpoint
            if (strpos($requestUri, '/api/pacientes/') !== false && isset($params[0]) && is_numeric($params[0])) {
                if ((int)$params[0] !== $usuario['paciente_id']) {
                    http_response_code(403);
                    echo json_encode(["erro" => "Acesso negado. Você só pode acessar seus próprios dados."]);
                    exit;
                }
            }
        }

        if (is_string($handler) && strpos($handler, '@') !== false) {
            list($controllerName, $method) = explode('@', $handler);
            // No need to require_once here anymore due to autoloader
            $controller = new $controllerName();
            return $controller->$method(...$params);
        }

        if (is_callable($handler)) {
            return $handler(...$params);
        }

        // Available to legacy procedural endpoints (anamnese endpoints)
        $pacienteId = $params[0] ?? null;

        require_once __DIR__ . "/" . $handler;
    };
}


$router = new Router();

$router->get("/api/planos", "PlanoController@list");
$router->post("/api/licencas", "LicencaController@store");



/*
|--------------------------------------------------------------------------
| Instituicoes +
|--------------------------------------------------------------------------
*/
$router->mount('/api/instituicoes', function () use ($router) {
    $router->post('/register', 'InstituicaoController@register');
    $router->get('/(\d+)', proteger(["instituicao.visualizar"], "InstituicaoController@show", true));
    $router->put('/', proteger(["instituicao.editar"], "InstituicaoController@update", true));
});



/*
|--------------------------------------------------------------------------
| AUTH +
|--------------------------------------------------------------------------
*/
$router->post("/api/auth/login", "AuthController@login");
$router->post("/api/auth/refresh", "AuthController@refresh");
$router->post("/api/auth/logout", "AuthController@logout");
$router->post("/api/auth/me", proteger([], "AuthController@me"));

/*
|--------------------------------------------------------------------------
| DASHBOARD STATS
|--------------------------------------------------------------------------
*/
$router->get("/api/dashboard/stats", proteger([], "DashboardController@getStats"));
$router->get("/api/dashboard/charts", proteger([], "DashboardController@getCharts"));

/*
|--------------------------------------------------------------------------
| USUÁRIOS + +
|--------------------------------------------------------------------------
*/

$router->get(
    "/api/usuarios",
    proteger(["usuario.listar"], "UsuarioController@list", true)
);

$router->get(
    "/api/usuarios/(\d+)",
    proteger(["usuario.visualizar"], "UsuarioController@show", true)
);

$router->post(
    "/api/usuarios",
    proteger(["usuario.criar"], "UsuarioController@store", true)
);

$router->put(
    "/api/usuarios/(\d+)",
    proteger(["usuario.editar"], "UsuarioController@update", true)
);

$router->delete(
    "/api/usuarios/(\d+)",
    proteger(["usuario.deletar"], "UsuarioController@delete", true)
);

// Papeis e permissoes
$router->get("/api/papeis", proteger(["papel.listar"], "PapelController@list", true));
$router->post("/api/papeis", proteger(["papel.criar"], "PapelController@store", true));
$router->delete("/api/papeis/(\d+)", proteger(["papel.deletar"], "PapelController@delete", true));

$router->get("/api/permissoes", proteger(["permissao.listar"], "PapelController@listPermissions", true));

$router->get("/api/papeis/permissoes/(\d+)", proteger(["permissao.listar"], "PapelController@getRolePermissions", true));
$router->put("/api/papeis/permissoes/(\d+)", proteger(["permissao.listar"], "PapelController@updatePermissions", true));

   // Procedural routes for papel/permissoes removed in favor of PapelController methods powyżej

// Administradores
$router->get("/api/admins", proteger(["admin.listar"], "AdminController@list", true));
$router->get("/api/admins/(\d+)", proteger(["admin.visualizar"], "UsuarioController@show", true));
$router->put("/api/admins/(\d+)", proteger(["admin.editar"], "AdminController@update", true));
$router->delete("/api/admins/(\d+)", proteger(["admin.deletar"], "AdminController@delete", true));
$router->post("/api/admins/papel", proteger(["papel.atribuir"], "AdminController@attachRole", true));

/*
|--------------------------------------------------------------------------
| MEDICOS + +
|--------------------------------------------------------------------------
*/

$router->get("/api/medicos", proteger(["medico.listar"], "MedicoController@list"));
$router->get("/api/medicos/(\d+)", proteger(["medico.visualizar"], "MedicoController@show"));
$router->get("/api/medicos/crm/([^/]+)", proteger(["medico.visualizar"], "MedicoController@showByCrm"));
$router->post("/api/medicos", proteger(["medico.criar"], "MedicoController@store"));
$router->put("/api/medicos/(\d+)", proteger(["medico.editar"], "MedicoController@update"));
$router->delete("/api/medicos/(\d+)", proteger(["medico.deletar"], "MedicoController@delete"));

$router->post("/api/medicos/(\d+)/pacientes", proteger(["medico.vincular_paciente"], "MedicoController@attachPaciente"));
$router->get("/api/medicos/(\d+)/pacientes", proteger(["medico.listar_pacientes"], "MedicoController@listPacientes"));
$router->delete("/api/medicos/(\d+)/pacientes/(\d+)", proteger(["medico.desvincular_paciente"], "MedicoController@detachPaciente"));


/*
|--------------------------------------------------------------------------
| PACIENTES + +
|--------------------------------------------------------------------------
*/

$router->get("/api/pacientes", proteger(["paciente.listar"], "PacienteController@list"));
$router->get("/api/pacientes/(\d+)", proteger(["paciente.visualizar"], "PacienteController@show"));
$router->get("/api/pacientes/cpf/([^/]+)", proteger(["paciente.visualizar"], "PacienteController@showByCpf"));
$router->post("/api/pacientes", proteger(["paciente.criar"], "PacienteController@store"));
$router->put("/api/pacientes/(\d+)", proteger(["paciente.editar"], "PacienteController@update"));
$router->delete("/api/pacientes/(\d+)", proteger(["paciente.deletar"], "PacienteController@delete"));


$router->post("/api/ia/analisar", proteger(["ia.criar"], "IAController@analyze"));
$router->get("/api/ia/historico", proteger(["ia.listar"], "IAController@list"));
$router->get("/api/pacientes/(\d+)/ia", proteger(["ia.listar"], "IAController@listByPaciente"));
$router->delete("/api/ia/(\d+)", proteger(["ia.deletar"], "IAController@delete"));

/*
|--------------------------------------------------------------------------
| CHAT / MENSAGENS
|--------------------------------------------------------------------------
*/
$router->get("/api/chat/contatos", proteger(["chat.listar"], "MensagemController@getContatos"));
$router->get("/api/chat/mensagens", proteger(["chat.listar"], "MensagemController@list"));
$router->post("/api/chat/mensagens", proteger(["chat.enviar"], "MensagemController@store"));

$router->get("/api/chat/usuario/(\d+)", proteger(["chat.listar"], "MensagemController@getChat"));
$router->post("/api/chat", proteger(["chat.enviar"], "MensagemController@store"));


/*
|--------------------------------------------------------------------------
| menssagens + +
|--------------------------------------------------------------------------
*/

$router->put(
    "/api/chat/(\d+)/lida",
    proteger(["chat.marcar_lida"], "MensagemController@marcarLida")
);



/*
|--------------------------------------------------------------------------
| auditoria +
|--------------------------------------------------------------------------
*/

// Auditoria
$router->get("/api/auditoria", proteger(["auditoria.listar"], "AuditoriaController@list"));
$router->get("/api/auditoria/paciente/(\d+)", proteger(["auditoria.listar"], "AuditoriaController@listByPaciente"));



// ANAMNESE (Ficha clínica do paciente)

// Diagnósticos
$router->get("/api/pacientes/(\d+)/diagnosticos", proteger(["anamnese.listar"], "AnamneseController@listDiagnosticos"));
$router->post("/api/pacientes/(\d+)/diagnosticos", proteger(["anamnese.criar"], "AnamneseController@storeDiagnostico"));
$router->put("/api/diagnosticos/(\d+)", proteger(["anamnese.editar"], "AnamneseController@updateDiagnostico"));
$router->delete("/api/diagnosticos/(\d+)", proteger(["anamnese.deletar"], "AnamneseController@deleteDiagnostico"));

// Sintomas
$router->get("/api/pacientes/(\d+)/sintomas", proteger(["anamnese.listar"], "AnamneseController@listSintomas"));
$router->post("/api/pacientes/(\d+)/sintomas", proteger(["anamnese.criar"], "AnamneseController@storeSintoma"));
$router->put("/api/sintomas/(\d+)", proteger(["anamnese.editar"], "AnamneseController@updateSintoma"));

// Histórico Médico
$router->get("/api/pacientes/(\d+)/historico-medico", proteger(["anamnese.listar"], "AnamneseController@listHistoricoMedico"));
$router->post("/api/pacientes/(\d+)/historico-medico", proteger(["anamnese.criar"], "AnamneseController@storeHistoricoMedico"));
$router->put("/api/historico-medico/(\d+)", proteger(["anamnese.editar"], "AnamneseController@updateHistoricoMedico"));

// Histórico Social
$router->get("/api/pacientes/(\d+)/historico-social", proteger(["anamnese.listar"], "AnamneseController@listHistoricoSocial"));
$router->post("/api/pacientes/(\d+)/historico-social", proteger(["anamnese.criar"], "AnamneseController@storeHistoricoSocial"));
$router->put("/api/historico-social/(\d+)", proteger(["anamnese.editar"], "AnamneseController@updateHistoricoSocial"));

// Qualidade Vida
$router->get("/api/pacientes/(\d+)/qualidade-vida", proteger(["anamnese.listar"], "AnamneseController@listQualidadeVida"));
$router->post("/api/pacientes/(\d+)/qualidade-vida", proteger(["anamnese.criar"], "AnamneseController@storeQualidadeVida"));
$router->put("/api/qualidade-vida/(\d+)", proteger(["anamnese.editar"], "AnamneseController@updateQualidadeVida"));

// Exame Físico
$router->get("/api/pacientes/(\d+)/exame-fisico", proteger(["anamnese.listar"], "AnamneseController@listExameFisico"));
$router->post("/api/pacientes/(\d+)/exame-fisico", proteger(["anamnese.criar"], "AnamneseController@storeExameFisico"));
$router->put("/api/exame-fisico/(\d+)", proteger(["anamnese.editar"], "AnamneseController@updateExameFisico"));

// Exames Complementares
$router->get("/api/pacientes/(\d+)/exames-complementares", proteger(["anamnese.listar"], "AnamneseController@listExamesComplementares"));
$router->post("/api/pacientes/(\d+)/exames-complementares", proteger(["anamnese.criar"], "AnamneseController@storeExamesComplementares"));
$router->put("/api/exames-complementares/(\d+)", proteger(["anamnese.editar"], "AnamneseController@updateExamesComplementares"));

// Plano Tratamento
$router->get("/api/pacientes/(\d+)/plano-tratamento", proteger(["anamnese.listar"], "AnamneseController@listPlanoTratamento"));
$router->post("/api/pacientes/(\d+)/plano-tratamento", proteger(["anamnese.criar"], "AnamneseController@storePlanoTratamento"));
$router->put("/api/plano-tratamento/(\d+)", proteger(["anamnese.editar"], "AnamneseController@updatePlanoTratamento"));

$router->run();
