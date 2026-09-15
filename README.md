# MedInsight AI

Sistema de gestão clínica multi-instituição com prontuário eletrônico, controle de acesso por papéis e diagnóstico assistido por IA a partir de imagens de exame.

Este repositório é a versão **refatorada** do TCC original ([TCC-2025](https://github.com/SamiLM4/TCC-2025)), com back-end reorganizado em MVC, front-end reconstruído e a camada de IA integrada diretamente ao sistema.

> Projeto acadêmico (Trabalho de Conclusão de Curso), aberto sob licença MIT.

---

## Visão geral

O MedInsight AI atende instituições de saúde (hospitais, clínicas) que precisam gerenciar médicos, pacientes e prontuários com controle de acesso granular, mantendo os dados de cada instituição isolados entre si (multi-tenant).

**Principais módulos:**

| Módulo | Descrição |
|---|---|
| **Instituições & Licenças** | Cadastro de instituições, planos de assinatura e licenças de uso |
| **Usuários & Papéis (RBAC)** | Usuários vinculados a papéis (admin, médico, paciente...) com permissões granulares por ação |
| **Médicos & Pacientes** | Cadastro completo, vínculo médico–paciente |
| **Prontuário / Anamnese** | Diagnósticos, sintomas, histórico médico e social, qualidade de vida, exame físico, exames complementares, plano de tratamento |
| **Diagnóstico por IA** | Upload de imagens de exame, redimensionamento automático e análise via OpenAI (GPT), com laudo estruturado salvo no histórico do paciente |
| **Chat interno** | Mensagens entre usuários do sistema |
| **Auditoria** | Log de ações sensíveis realizadas no sistema |
| **Dashboard** | Estatísticas e gráficos de uso da instituição |

---

## Stack

**Back-end**
- PHP 8.2, sem framework — MVC próprio (`controle/` + `modelo/`) com roteador customizado ([modelo/Router.php](modelo/Router.php))
- MySQL / MariaDB via `mysqli`
- Autenticação por JWT ([firebase/php-jwt](https://github.com/firebase/php-jwt)), com controle de permissões por rota
- [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv) para variáveis de ambiente
- [PHPMailer](https://github.com/PHPMailer/PHPMailer) para envio de e-mails
- Geração de PDF via [FPDF](http://www.fpdf.org/)
- Integração com a API da OpenAI para o módulo de diagnóstico por IA

**Front-end**
- HTML, CSS e JavaScript puros (sem build step, sem framework)
- Consome a API REST do back-end via `fetch`

---

## Estrutura do projeto

```
├── controle/        # Controllers (regras de rota → lógica de negócio)
├── modelo/           # Models (acesso a dados) + Router + utilitários
├── middleware/        # Middleware de autenticação/autorização
├── config/            # Configurações (JWT, etc.)
├── services/          # Serviços auxiliares (ex.: envio de e-mail)
├── front/             # Front-end (HTML/CSS/JS)
├── banco/             # Script SQL de criação do banco
├── fpdf/              # Biblioteca de geração de PDF
├── index.php          # Front controller — bootstrap, CORS, rotas da API
└── index.html          # Landing page
```

O roteamento segue o padrão *front controller*: toda requisição HTTP passa por `index.php` (via `.htaccess`), que despacha para o `Controller@metodo` correspondente, opcionalmente protegido por permissão (`proteger([...])`).

---

## Como rodar localmente

### Pré-requisitos
- PHP >= 8.1 com extensões `mysqli` e `gd` (esta última usada para redimensionar imagens de exame)
- MySQL/MariaDB
- [Composer](https://getcomposer.org/)
- Apache (ou qualquer servidor com suporte a `.htaccess`/`mod_rewrite`) — ou o servidor embutido do PHP para testes rápidos

### 1. Instalar dependências
```bash
composer install
```

### 2. Configurar variáveis de ambiente
Crie um arquivo `.env` na raiz do projeto:
```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=TCC25
DB_USER=root
DB_PASS=

EMAIL_USER=
EMAIL_PASS=

OPENAI_API_KEY=
IA_DEV_MODE=false
```
- `IA_DEV_MODE=true` permite testar o fluxo de diagnóstico por IA sem uma chave da OpenAI válida (retorna um laudo simulado).
- `EMAIL_USER`/`EMAIL_PASS` são usados pelo [EmailService](services/EmailService.php) para notificações por e-mail.

### 3. Criar o banco de dados
```bash
mysql -u root -p < banco/banco_tcc.sql
```

### 4. Subir o servidor
Com Apache/XAMPP, aponte o document root para a raiz do projeto e acesse `http://localhost/`.

Para um teste rápido sem Apache:
```bash
php -S localhost:8000 index.php   # API
php -S localhost:8080             # front-end estático, na mesma pasta
```

Acesse `login.html` para autenticar ou `register_institution.html` para cadastrar uma nova instituição.

---

## Segurança

- Autenticação via JWT com expiração configurável ([config/jwt.php](config/jwt.php)) e refresh tokens.
- Autorização por permissão granular em cada rota (`proteger(["recurso.acao"], ...)` em [index.php](index.php)), checada pelo [middleware de autenticação](middleware/AuthMiddleware.php).
- Isolamento multi-tenant: toda consulta a dados de paciente/médico valida `instituicao_id`.
- Pacientes só acessam os próprios dados (checagem explícita no front controller).

> ⚠️ **Atenção:** o segredo usado para assinar os tokens JWT está hard-coded em [config/jwt.php](config/jwt.php) e versionado no repositório. Antes de qualquer uso além de desenvolvimento local, mova-o para uma variável de ambiente (`JWT_SECRET` no `.env`) e gere um novo segredo — o valor atual deve ser considerado comprometido por estar público no histórico do Git.

---

## Licença

Distribuído sob a licença [MIT](LICENSE).

## Autor

**Murilo Lima** — [GitHub](https://github.com/SamiLM4)
