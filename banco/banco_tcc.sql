CREATE DATABASE IF NOT EXISTS TCC25 CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE TCC25;

CREATE TABLE instituicao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cep VARCHAR(12) NOT NULL,
    logradouro TEXT NOT NULL,
    cidade VARCHAR(60) NOT NULL,
    bairro VARCHAR(50) NOT NULL,
    cnpj VARCHAR(20) UNIQUE NOT NULL,
    tipo ENUM('publico', 'privado', 'filantropico') NOT NULL,
    telefone VARCHAR(25) NOT NULL,
    email VARCHAR(155) NOT NULL,
    site TEXT,
    atividade ENUM('ativo', 'inativo') DEFAULT 'ativo',
    nome_responsavel VARCHAR(100) NOT NULL,
    telefone_responsavel VARCHAR(20) NOT NULL
);

CREATE TABLE planos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT NULL,

    limite_usuarios INT NULL,
    limite_papeis INT NULL,

    limite_ia INT NULL,

    valor DECIMAL(10,2) NOT NULL,
    duracao_dias INT NOT NULL,

    ativo BOOLEAN DEFAULT TRUE,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE licencas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(512) NOT NULL UNIQUE,
    instituicao_id INT,
    status ENUM('ativa','inativa') DEFAULT 'ativa',
    usado BOOLEAN DEFAULT FALSE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expira_em TIMESTAMP NULL,
    plano_id INT NOT NULL,

    FOREIGN KEY (plano_id) REFERENCES planos(id),
    FOREIGN KEY (instituicao_id) REFERENCES instituicao(id) ON DELETE CASCADE
);

CREATE TABLE papeis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    descricao TEXT,
    instituicao_id INT NOT NULL,
    is_delete TINYINT(1) NOT NULL DEFAULT 0,

    UNIQUE (nome, instituicao_id),
    FOREIGN KEY (instituicao_id) REFERENCES instituicao(id) ON DELETE CASCADE
);

CREATE TABLE permissoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) UNIQUE NOT NULL,
    descricao TEXT
);

CREATE TABLE papeis_permissoes (
    papel_id INT NOT NULL,
    permissao_id INT NOT NULL,
    PRIMARY KEY (papel_id, permissao_id),

    FOREIGN KEY (papel_id) REFERENCES papeis(id) ON DELETE CASCADE,
    FOREIGN KEY (permissao_id) REFERENCES permissoes(id) ON DELETE CASCADE
);

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instituicao_id INT NOT NULL,

    email VARCHAR(150) NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    admin_owner TINYINT(1) NOT NULL DEFAULT 0,

    nome VARCHAR(150) NULL,
    cpf VARCHAR(11) NOT NULL,
    crm VARCHAR(20) NULL,
    especialidade VARCHAR(100) NULL,

    ativo BOOLEAN DEFAULT TRUE,
    ultimo_login TIMESTAMP NULL,
    tentativas_login INT DEFAULT 0,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    UNIQUE (instituicao_id, email),
    UNIQUE (cpf),

    CONSTRAINT fk_usuario_instituicao
        FOREIGN KEY (instituicao_id)
        REFERENCES instituicao(id)
);

CREATE TABLE usuarios_papeis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instituicao_id INT NOT NULL,
    usuario_id INT NOT NULL,
    papel_id INT NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (instituicao_id) REFERENCES instituicao(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (papel_id) REFERENCES papeis(id)
);

CREATE TABLE historico_acessos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    ip VARCHAR(45),
    user_agent TEXT,
    data_acesso TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE refresh_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    token VARCHAR(512) NOT NULL UNIQUE,
    expira_em TIMESTAMP NOT NULL,
    revogado BOOLEAN DEFAULT FALSE,
    deleted_at TIMESTAMP NULL,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL UNIQUE,
    instituicao_id INT NOT NULL,
    nome VARCHAR(150) NOT NULL,
    admin_owner TINYINT(1) NOT NULL DEFAULT 0,
    deleted_at TIMESTAMP NULL DEFAULT NULL,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (instituicao_id) REFERENCES instituicao(id) ON DELETE CASCADE
);

-- Não existe uma tabela "medicos" própria: médicos são usuarios com
-- papel MEDICO, usando as colunas crm/especialidade já presentes em
-- "usuarios" (ver modelo/MedicoModel.php).

CREATE TABLE pacientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNIQUE NULL,
    instituicao_id INT NOT NULL,
    cpf VARCHAR(11) NOT NULL UNIQUE,
    nome VARCHAR(100) NOT NULL,
    sexo CHAR(1),
    endereco TEXT,
    telefone VARCHAR(50),
    profissao VARCHAR(255),
    estado_civil VARCHAR(50),
    nome_cuidador VARCHAR(100),
    telefone_cuidador VARCHAR(50),
    deleted_at TIMESTAMP NULL,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (instituicao_id) REFERENCES instituicao(id) ON DELETE CASCADE
);

-- Vínculo médico <-> paciente. medico_id referencia usuarios(id)
-- (papel MEDICO), não uma tabela "medicos".
CREATE TABLE medico_paciente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medico_id INT NOT NULL,
    paciente_id INT NOT NULL,
    deleted_at TIMESTAMP NULL,
    UNIQUE (medico_id, paciente_id),

    FOREIGN KEY (medico_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE
);

CREATE TABLE auditoria_medica (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    paciente_id INT,
    acao VARCHAR(100),
    descricao TEXT,
    ip VARCHAR(45),
    data_acao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    instituicao_id INT NOT NULL,

    FOREIGN KEY (instituicao_id) REFERENCES instituicao(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE NO ACTION
);

-- ==========================================================
-- Anamnese (prontuário). Todas as tabelas abaixo precisam de
-- deleted_at: controle/AnamneseController.php faz soft-delete e
-- filtra "deleted_at IS NULL" genericamente para qualquer uma delas.
-- ==========================================================

CREATE TABLE diagnosticos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instituicao_id INT NOT NULL,
    paciente_id INT NOT NULL,
    data_diagnostico DATE,
    tipo_em VARCHAR(5),
    surtos TEXT,
    deleted_at TIMESTAMP NULL,

    FOREIGN KEY (instituicao_id) REFERENCES instituicao(id) ON DELETE CASCADE,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE
);

CREATE TABLE sintomas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instituicao_id INT NOT NULL,
    paciente_id INT NOT NULL,
    sintomas_iniciais TEXT,
    sintomas_atuais TEXT,
    fadiga BOOLEAN,
    problema_visao VARCHAR(100),
    problema_equilibrio BOOLEAN,
    problema_coordenacao BOOLEAN,
    espaticidade BOOLEAN,
    fraqueza_muscular BOOLEAN,
    problema_sensibilidade VARCHAR(100),
    problema_bexiga BOOLEAN,
    problema_intestino BOOLEAN,
    problema_cognitivo VARCHAR(255),
    problema_emocional VARCHAR(255),
    deleted_at TIMESTAMP NULL,

    FOREIGN KEY (instituicao_id) REFERENCES instituicao(id) ON DELETE CASCADE,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE
);

CREATE TABLE historico_medico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instituicao_id INT NOT NULL,
    paciente_id INT NOT NULL,
    medicamento_em_uso TEXT,
    tratamentos_anteriores_em TEXT,
    alergias TEXT,
    historico_outras_doencas TEXT,
    historico_familiar TEXT,
    deleted_at TIMESTAMP NULL,

    FOREIGN KEY (instituicao_id) REFERENCES instituicao(id) ON DELETE CASCADE,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE
);

CREATE TABLE historico_social (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instituicao_id INT NOT NULL,
    paciente_id INT NOT NULL,
    tabagismo VARCHAR(50),
    alcool VARCHAR(100),
    atividade_fisica TEXT,
    suporte_social TEXT,
    impacto_profissional_social TEXT,
    deleted_at TIMESTAMP NULL,

    FOREIGN KEY (instituicao_id) REFERENCES instituicao(id) ON DELETE CASCADE,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE
);

CREATE TABLE qualidade_vida_em (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instituicao_id INT NOT NULL,
    paciente_id INT NOT NULL,
    edss FLOAT,
    questionario_msqol54 TEXT,
    outras_avaliacoes TEXT,
    deleted_at TIMESTAMP NULL,

    FOREIGN KEY (instituicao_id) REFERENCES instituicao(id) ON DELETE CASCADE,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE
);

CREATE TABLE exame_fisico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instituicao_id INT NOT NULL,
    paciente_id INT NOT NULL,
    exame_neurologico TEXT,
    forca_muscular TEXT,
    reflexos TEXT,
    coordenacao TEXT,
    sensibilidade TEXT,
    equilibrio TEXT,
    funcao_visual TEXT,
    outros_exames_fisicos TEXT,
    deleted_at TIMESTAMP NULL,

    FOREIGN KEY (instituicao_id) REFERENCES instituicao(id) ON DELETE CASCADE,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE
);

CREATE TABLE exames_complementares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instituicao_id INT NOT NULL,
    paciente_id INT NOT NULL,
    rm_cerebro_medula TEXT,
    potenciais_evocados_visuais TEXT,
    potenciais_evocados_somatossensoriais TEXT,
    potenciais_evocados_auditivos_de_tronco_encefalico TEXT,
    analise_do_liquido_cefalorraquidiano TEXT,
    outros_exames TEXT,
    deleted_at TIMESTAMP NULL,

    FOREIGN KEY (instituicao_id) REFERENCES instituicao(id) ON DELETE CASCADE,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE
);

CREATE TABLE plano_tratamento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instituicao_id INT NOT NULL,
    paciente_id INT NOT NULL,
    medicamentos_modificadores_doenca TEXT,
    tratamento_surtos TEXT,
    tratamento_sintomas TEXT,
    reabilitacao TEXT,
    acompanhamento_psicologico TEXT,
    outras_terapias TEXT,
    deleted_at TIMESTAMP NULL,

    FOREIGN KEY (instituicao_id) REFERENCES instituicao(id) ON DELETE CASCADE,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE
);

CREATE TABLE ia_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instituicao_id INT NOT NULL,
    paciente_id INT NOT NULL,
    nome VARCHAR(150),
    cpf VARCHAR(20),
    imagem LONGTEXT,
    diagnostico TEXT,
    data_diagnostico DATE,

    FOREIGN KEY (instituicao_id) REFERENCES instituicao(id) ON DELETE CASCADE,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE
);

CREATE TABLE mensagens_chat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instituicao_id INT NOT NULL,
    usuario_id INT NOT NULL,
    para_usuario_id INT NULL,
    mensagem TEXT NOT NULL,
    origem_papel_id INT NOT NULL,
    data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    lida BOOLEAN DEFAULT FALSE,
    deleted_at TIMESTAMP NULL,

    FOREIGN KEY (origem_papel_id) REFERENCES papeis(id),
    FOREIGN KEY (instituicao_id) REFERENCES instituicao(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (para_usuario_id) REFERENCES usuarios(id)
);

CREATE INDEX idx_usuarios_email ON usuarios(email);
CREATE INDEX idx_auditoria_usuario ON auditoria_medica(usuario_id);
CREATE INDEX idx_auditoria_paciente ON auditoria_medica(paciente_id);
CREATE INDEX idx_refresh_usuario ON refresh_tokens(usuario_id);
CREATE INDEX idx_usuarios_deleted ON usuarios(deleted_at);

-- ==========================================================
-- Permissões
-- ==========================================================

INSERT INTO permissoes (nome) VALUES
('usuario.listar'),
('usuario.visualizar'),
('usuario.criar'),
('usuario.editar'),
('usuario.deletar'),

('papel.listar'),
('papel.criar'),
('papel.deletar'),
('papel.vincular_permissao'),
('permissao.listar'),

('admin.listar'),
('admin.visualizar'),
('papel.atribuir'),
('admin.editar'),
('admin.deletar'),

('medico.listar'),
('medico.visualizar'),
('medico.criar'),
('medico.editar'),
('medico.deletar'),
('medico.vincular_paciente'),
('medico.listar_pacientes'),
('medico.desvincular_paciente'),

('paciente.listar'),
('paciente.visualizar'),
('paciente.criar'),
('paciente.editar'),
('paciente.deletar'),

('ia.criar'),
('ia.listar'),
('ia.deletar'),
('ia.visualizar_imagem'),

('chat.enviar'),
('chat.listar'),
('chat.marcar_lida'),
('chat.visualizar'),

('auditoria.listar'),
('auditoria.criar'),

('anamnese.listar'),
('anamnese.criar'),
('anamnese.editar'),
('anamnese.deletar'),

('instituicao.visualizar'),
('instituicao.criar'),
('instituicao.editar'),
('instituicao.deletar'),

('licenca.visualizar'),
('licenca.criar'),
('licenca.ativar'),
('licenca.renovar'),

('plano.alterar'),
('plano.visualizar');

INSERT INTO planos
(nome, descricao, limite_usuarios, limite_papeis, limite_ia, valor, duracao_dias)
VALUES
('Básico', 'Plano ideal para clínicas pequenas', 5, 1, 50, 199.90, 30),
('Profissional', 'Plano intermediário', 15, 3, 300, 499.90, 30),
('Enterprise', 'Ilimitado para grandes instituições', NULL, NULL, NULL, 1299.90, 30);

-- ==========================================================
-- Dados de exemplo para desenvolvimento local
-- Login: admin@teste.com / medico@teste.com / paciente@teste.com
-- Senha (todos): "senha123"
-- ==========================================================

INSERT INTO instituicao (nome, cep, logradouro, cidade, bairro, cnpj, tipo, telefone, email, site, nome_responsavel, telefone_responsavel) VALUES
('Hospital Teste', '12200-000', 'Rua A', 'São José', 'Centro', '12345678000199', 'privado', '(12)99999-9999', 'contato@hospital.com', 'https://hospital.com', 'Diretor Teste', '(12)98888-8888');

INSERT INTO licencas (token, instituicao_id, status, usado, expira_em, plano_id) VALUES
(SHA2(CONCAT('dev-license-', UUID()), 256), 1, 'ativa', TRUE, DATE_ADD(NOW(), INTERVAL 365 DAY), 3);

INSERT INTO papeis (nome, descricao, instituicao_id) VALUES
('ADMIN', 'Administrador do sistema', 1),
('MEDICO', 'Médico responsável', 1),
('PACIENTE', 'Paciente cadastrado', 1);

-- ADMIN: todas as permissões
INSERT INTO papeis_permissoes (papel_id, permissao_id)
SELECT (SELECT id FROM papeis WHERE nome = 'ADMIN' AND instituicao_id = 1), id FROM permissoes;

-- MEDICO: gestão de pacientes, prontuário, IA e chat
INSERT INTO papeis_permissoes (papel_id, permissao_id)
SELECT (SELECT id FROM papeis WHERE nome = 'MEDICO' AND instituicao_id = 1), id FROM permissoes
WHERE nome IN (
    'medico.listar','medico.visualizar','medico.editar',
    'medico.vincular_paciente','medico.listar_pacientes','medico.desvincular_paciente',
    'paciente.listar','paciente.visualizar','paciente.criar','paciente.editar',
    'anamnese.listar','anamnese.criar','anamnese.editar',
    'ia.criar','ia.listar','ia.visualizar_imagem',
    'chat.enviar','chat.listar','chat.marcar_lida','chat.visualizar'
);

-- PACIENTE: acesso restrito aos próprios dados
INSERT INTO papeis_permissoes (papel_id, permissao_id)
SELECT (SELECT id FROM papeis WHERE nome = 'PACIENTE' AND instituicao_id = 1), id FROM permissoes
WHERE nome IN (
    'paciente.visualizar','anamnese.listar',
    'ia.listar','ia.visualizar_imagem',
    'chat.enviar','chat.listar','chat.visualizar'
);

-- Usuários de teste (senha "senha123" para os três, hash bcrypt)
INSERT INTO usuarios (instituicao_id, email, senha_hash, admin_owner, nome, cpf) VALUES
(1, 'admin@teste.com', '$2y$10$X/afuQ0lkshdUCMYu39EYuDaHOga5PpLBzPeG8oD./2Ziq9ybFfUu', 1, 'Admin Teste', '11111111111');

INSERT INTO usuarios (instituicao_id, email, senha_hash, admin_owner, nome, cpf, crm, especialidade) VALUES
(1, 'medico@teste.com', '$2y$10$X/afuQ0lkshdUCMYu39EYuDaHOga5PpLBzPeG8oD./2Ziq9ybFfUu', 0, 'Dr. Médico Teste', '22222222222', 'SP123456', 'Neurologia');

INSERT INTO usuarios (instituicao_id, email, senha_hash, admin_owner, nome, cpf) VALUES
(1, 'paciente@teste.com', '$2y$10$X/afuQ0lkshdUCMYu39EYuDaHOga5PpLBzPeG8oD./2Ziq9ybFfUu', 0, 'Paciente Teste', '33333333333');

INSERT INTO usuarios_papeis (instituicao_id, usuario_id, papel_id) VALUES
(1, (SELECT id FROM usuarios WHERE email = 'admin@teste.com'), (SELECT id FROM papeis WHERE nome = 'ADMIN' AND instituicao_id = 1)),
(1, (SELECT id FROM usuarios WHERE email = 'medico@teste.com'), (SELECT id FROM papeis WHERE nome = 'MEDICO' AND instituicao_id = 1)),
(1, (SELECT id FROM usuarios WHERE email = 'paciente@teste.com'), (SELECT id FROM papeis WHERE nome = 'PACIENTE' AND instituicao_id = 1));

INSERT INTO pacientes (usuario_id, instituicao_id, cpf, nome, sexo, telefone) VALUES
((SELECT id FROM usuarios WHERE email = 'paciente@teste.com'), 1, '98765432100', 'Paciente Teste', 'M', '(12)98888-1111');

INSERT INTO medico_paciente (medico_id, paciente_id) VALUES
((SELECT id FROM usuarios WHERE email = 'medico@teste.com'), (SELECT id FROM pacientes WHERE cpf = '98765432100'));

INSERT INTO mensagens_chat (instituicao_id, usuario_id, mensagem, origem_papel_id) VALUES
(1, (SELECT id FROM usuarios WHERE email = 'medico@teste.com'), 'Olá, paciente! Como você está se sentindo hoje?', (SELECT id FROM papeis WHERE nome = 'MEDICO' AND instituicao_id = 1)),
(1, (SELECT id FROM usuarios WHERE email = 'medico@teste.com'), 'Agendamento confirmado para a próxima semana.', (SELECT id FROM papeis WHERE nome = 'MEDICO' AND instituicao_id = 1));
