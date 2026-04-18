<?php

require_once __DIR__ . "/BaseController.php";
require_once __DIR__ . "/../modelo/MensagemModel.php";

class MensagemController extends BaseController {
    private $model;

    public function __construct() {
        $this->model = new MensagemModel();
    }

    public function getContatos() {
        $usuario = $GLOBALS['usuario'];
        $instituicaoId = $usuario['instituicao_id'];
        
        // Se for paciente, vê apenas profissionais (ADMIN/MEDICO)
        // Se for profissional, vê todos? Ou apenas outros profissionais?
        // O usuário pediu: "pacientes também podem enviar mensagens para os médicos"
        
        $sql = "
            SELECT u.id, u.email, u.nome,
                   GROUP_CONCAT(p.nome) as papeis
            FROM usuarios u
            LEFT JOIN usuarios_papeis up ON up.usuario_id = u.id
            LEFT JOIN papeis p ON p.id = up.papel_id
            WHERE u.instituicao_id = ? AND u.deleted_at IS NULL AND u.id != ?
            GROUP BY u.id
        ";
        
        $result = $this->model->query($sql, [$instituicaoId, $usuario['id']], "ii");
        $contatos = [];
        while ($row = $result->fetch_assoc()) {
            $contatos[] = $row;
        }
        
        $this->jsonResponse($contatos);
    }

    public function list() {
        $usuario = $GLOBALS['usuario'];
        $data = $this->model->getUltimasMensagens($usuario['id'], $usuario['instituicao_id']);
        $this->jsonResponse($data);
    }

    public function getChat($outroUsuarioId) {
        $usuario = $GLOBALS['usuario'];
        $data = $this->model->getConversa($usuario['id'], $outroUsuarioId, $usuario['instituicao_id']);
        $this->jsonResponse($data);
    }

    public function store() {
        $usuario = $GLOBALS['usuario'];
        $input = json_decode(file_get_contents("php://input"), true);

        if (empty($input['mensagem']) || empty($input['para_usuario_id'])) {
            $this->errorResponse("Mensagem e destinatário são obrigatórios");
        }

        $msgId = $this->model->create([
            "instituicao_id" => $usuario['instituicao_id'],
            "usuario_id" => $usuario['id'],
            "para_usuario_id" => (int)$input['para_usuario_id'],
            "mensagem" => $input['mensagem'],
            "data_envio" => date('Y-m-d H:i:s')
        ]);

        $this->registrarAuditoria('Enviou Mensagem', "Enviou mensagem para usuário ID: " . $input['para_usuario_id']);

        $this->jsonResponse(["id" => $msgId, "msg" => "Mensagem enviada"]);
    }

    public function marcarLida($id) {
        $usuario = $GLOBALS['usuario'];
        $this->model->marcarLida($id, $usuario['instituicao_id']);
        $this->jsonResponse(["msg" => "Mensagem marcada como lida"]);
    }
}
