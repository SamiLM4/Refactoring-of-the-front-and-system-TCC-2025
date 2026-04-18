create database TCC25;
use TCC25;
    
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

INSERT INTO planos
(nome, descricao, limite_usuarios, limite_papeis, limite_ia, valor, duracao_dias)
VALUES
(
'Básico',
'Plano ideal para clínicas pequenas',
5, 1, 50,
199.90,
30
),
(
'Profissional',
'Plano intermediário',
15, 3, 300,
499.90,
30
),
(
'Enterprise',
'Ilimitado para grandes instituições',
NULL, NULL, NULL,
1299.90,
30
);

CREATE TABLE papeis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) UNIQUE NOT NULL,
    descricao TEXT,
    instituicao_id INT NOT NULL,
    is_delete TINYINT(1) NOT NULL DEFAULT 0,
    
    FOREIGN KEY (instituicao_id)
	REFERENCES instituicao(id)
	ON DELETE CASCADE

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
    instituicao_id int not null,
    usuario_id INT NOT NULL,
    papel_id INT NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

	foreign key (instituicao_id) references instituicao(id) on delete cascade,
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

/*
	CREATE TABLE medicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL UNIQUE,
    instituicao_id INT NOT NULL,
    cpf VARCHAR(11) NOT NULL UNIQUE,
    crm VARCHAR(11) NOT NULL UNIQUE,
    nome VARCHAR(100) NOT NULL,
    deleted_at TIMESTAMP NULL,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (instituicao_id) REFERENCES instituicao(id) ON DELETE CASCADE

);
*/

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

CREATE TABLE auditoria_medica (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    paciente_id INT NOT NULL,
    acao VARCHAR(100),
    descricao TEXT,
    ip VARCHAR(45),
    data_acao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    instituicao_id int not null,

	FOREIGN KEY (instituicao_id) REFERENCES instituicao(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE NO ACTION

);        
/*
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

    FOREIGN KEY (instituicao_id) REFERENCES instituicao(id) ON DELETE CASCADE,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE
);
*/
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

 /*     
CREATE TABLE medico_paciente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medico_id INT,
    paciente_id INT NOT NULL,
    UNIQUE (medico_id, paciente_id),
    deleted_at TIMESTAMP NULL,

    FOREIGN KEY (medico_id) REFERENCES medicos(id)ON DELETE CASCADE,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE
);
*/    
CREATE TABLE mensagens_chat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instituicao_id INT NOT NULL,
    usuario_id INT NOT NULL,
    mensagem TEXT NOT NULL,
    origem_papel_id INT NOT NULL,
    data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    lida BOOLEAN DEFAULT FALSE,
    deleted_at TIMESTAMP NULL,

	FOREIGN KEY (origem_papel_id) REFERENCES papeis(id),
    FOREIGN KEY (instituicao_id) REFERENCES instituicao(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE INDEX idx_usuarios_email ON usuarios(email);
CREATE INDEX idx_auditoria_usuario ON auditoria_medica(usuario_id);
CREATE INDEX idx_auditoria_paciente ON auditoria_medica(paciente_id);
CREATE INDEX idx_refresh_usuario ON refresh_tokens(usuario_id);
CREATE INDEX idx_usuarios_deleted ON usuarios(deleted_at);

ALTER TABLE papeis DROP INDEX nome;
ALTER TABLE papeis
ADD UNIQUE (nome, instituicao_id);

## TEST

USE TCC25;

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
/*
('anamnese.listar'),
('anamnese.criar'),
('anamnese.editar'),
('anamnese.deletar'),
*/
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

/*
INSERT INTO usuarios (email, senha_hash)
VALUES
('admin@teste.com', '$2y$10$md.r9c9rnGG9XgMUm3xApO9t94g1TsTdK8IcQ1S3q1g9pbTR5wlzO'),
('medico@teste.com', '$2y$10$md.r9c9rnGG9XgMUm3xApO9t94g1TsTdK8IcQ1S3q1g9pbTR5wlzO'),
('paciente@teste.com', '$2y$10$md.r9c9rnGG9XgMUm3xApO9t94g1TsTdK8IcQ1S3q1g9pbTR5wlzO');

#########################################

-- =========================
-- INSTITUICAO
-- =========================
INSERT INTO instituicao (nome, cep, logradouro, cidade, bairro, cnpj, tipo, telefone, email, site, nome_responsavel, telefone_responsavel) VALUES
('Hospital Teste', '12200-000', 'Rua A', 'São José', 'Centro', '12345678000199', 'privado', '(12)99999-9999', 'contato@hospital.com', 'https://hospital.com', 'Diretor Teste', '(12)98888-8888'),
('Clinica Saúde', '12210-111', 'Rua B', 'São José', 'Vila Nova', '12345678000299', 'filantropico', '(12)98888-7777', 'contato@clinica.com', 'https://clinica.com', 'Dr. Saúde', '(12)97777-7777'),
('Hospital Universitário', '12220-222', 'Rua C', 'São José', 'Jardim', '12345678000399', 'publico', '(12)96666-6666', 'contato@universitario.com', 'https://universitario.com', 'Prof. Diretor', '(12)95555-5555');

INSERT INTO papeis (nome, descricao, instituicao_id) VALUES
('ADMIN', 'Administrador do sistema', 1),
('MEDICO', 'Médico responsável', 1),
('PACIENTE', 'Paciente cadastrado', 1);


INSERT INTO pacientes (usuario_id, instituicao_id, cpf, nome, sexo, telefone) VALUES
(3, 1, '98765432100', 'Paciente A', 'M', '(12)98888-1111');

INSERT INTO mensagens_chat (instituicao_id, usuario_id, mensagem, origem_papel_id) VALUES
(1, 2, 'Olá, paciente A!', 2),
(1, 2, 'Agendamento confirmado.', 2),
(1, 2, 'Lembrete de consulta.', 2);
##############################################################################################

-- =========================
-- ADMIN (total)
-- =========================
INSERT INTO papeis_permissoes (papel_id, permissao_id) VALUES
(1, 1),(1, 2),(1, 3),(1, 4),(1, 5), -- usuarios
(1, 6),(1, 7),(1, 8),(1, 9),        -- papeis/permissoes
(1, 10),(1, 11),(1, 12),(1, 13),(1, 14), -- admins
(1, 15),(1, 16),(1, 17),(1, 18),(1, 19),(1, 20),(1, 21),(1, 22), -- medicos
(1, 23),(1, 24),(1, 25),(1, 26),(1, 27), -- pacientes
(1, 28),(1, 29),(1, 30),(1, 31),         -- IA
(1, 32),(1, 33),(1, 34),(1, 35),         -- Chat
(1, 36),(1, 37),                          -- Auditoria
(1, 38),(1, 39),(1, 40),(1, 41);         -- Anamnese

-- =========================
-- MÉDICO (relevante)
-- =========================
INSERT INTO papeis_permissoes (papel_id, permissao_id) VALUES
(2, 15),(2, 16),(2, 17),(2, 18),(2, 19), -- médicos (listar, criar, editar)
(2, 20),(2, 21),(2, 22),                  -- vincular/desvincular paciente
(2, 23),(2, 24),(2, 26),                  -- pacientes (listar, visualizar, editar)
(2, 28),(2, 29),(2, 31),                  -- IA (criar, listar, visualizar_imagem)
(2, 32),(2, 33),(2, 34),(2, 35),          -- Chat
(2, 38),(2, 39),(2, 40);                  -- Anamnese (listar, criar, editar)

-- =========================
-- PACIENTE (limitado)
-- =========================
INSERT INTO papeis_permissoes (papel_id, permissao_id) VALUES
(3, 24), -- paciente.visualizar
(3, 32),(3, 33),(3, 35), -- chat.enviar, chat.listar, chat.visualizar
(3, 31), -- IA.visualizar_imagem
(3, 38); -- anamnese.listar

##############################################################################################
INSERT INTO usuarios_papeis (usuario_id, papel_id) VALUES
(1, 1), -- admin
(2, 2), -- medico
(3, 3); -- paciente
*/
#drop database tcc25;