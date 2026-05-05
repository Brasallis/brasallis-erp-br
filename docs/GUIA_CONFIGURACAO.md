# 📘 Guia de Configuração — Brasallis Hub
**Versão:** 2026-05-04 | **Ambiente:** Produção

---

## Índice

1. [Configurações de Ambiente (`.env`)](#1-configurações-de-ambiente-env)
2. [Banco de Dados MySQL](#2-banco-de-dados-mysql)
3. [Envio de E-mail (SMTP / PHPMailer)](#3-envio-de-e-mail-smtp--phpmailer)
4. [Login com Google (OAuth 2.0)](#4-login-com-google-oauth-20)
5. [Chave Mestre e Scripts de Manutenção](#5-chave-mestre-e-scripts-de-manutenção)
6. [Segurança: Checklist Final para Produção](#6-segurança-checklist-final-para-produção)

---

## 1. Configurações de Ambiente (`.env`)

O arquivo `.env` na raiz do projeto controla todo o comportamento do sistema.
**Nunca commite este arquivo com credenciais reais.**

### 1.1 — Copiar o arquivo de exemplo

```bash
cp .env.example .env
```

### 1.2 — Variáveis obrigatórias

Abra o `.env` e preencha os campos abaixo:

```env
APP_NAME="BRASALLIS HUB"
APP_ENV=production        # ← Trocar de "local" para "production"
APP_DEBUG=false           # ← Trocar de "true" para "false"
APP_URL=https://seudominio.com.br
```

> [!CAUTION]
> Em produção, `APP_ENV` **deve** ser `production` e `APP_DEBUG` **deve** ser `false`.
> Deixar `APP_DEBUG=true` expõe erros internos do PHP para qualquer visitante.

---

## 2. Banco de Dados MySQL

### 2.1 — Criar usuário dedicado (não use root em produção)

Conecte ao MySQL como root e execute:

```sql
-- Criar usuário dedicado para a aplicação
CREATE USER 'brasallis_app'@'localhost' IDENTIFIED BY 'SUA_SENHA_FORTE_AQUI';

-- Conceder apenas as permissões necessárias
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX
    ON gerenciador_estoque.*
    TO 'brasallis_app'@'localhost';

FLUSH PRIVILEGES;
```

### 2.2 — Atualizar o `.env` com o novo usuário

```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=gerenciador_estoque
DB_USER=brasallis_app       # ← Usuário criado acima (não root)
DB_PASS=SUA_SENHA_FORTE_AQUI
```

### 2.3 — Executar a migration de segurança

Este comando adiciona as colunas necessárias para o Google OAuth e reset de senha seguro:

```bash
# Via Docker
docker exec brasallis-db mysql -u root -pSUA_SENHA gerenciador_estoque \
  < database/migrations/2026_05_04_google_oauth_reset.sql

# Ou diretamente no servidor
mysql -u root -p gerenciador_estoque < database/migrations/2026_05_04_google_oauth_reset.sql
```

**O que a migration faz:**
- Adiciona `google_sub` em `usuarios` (vincula conta Google)
- Adiciona `token` e `expires_at` em `redefinicoes_senha` (reset seguro com expiração de 1h)
- Adiciona índices de performance em `system_logs`

---

## 3. Envio de E-mail (SMTP / PHPMailer)

O sistema usa PHPMailer para enviar e-mails de redefinição de senha.

### 3.1 — Opção A: Gmail (recomendado para pequenas equipes)

**Passo 1:** Acesse [myaccount.google.com/security](https://myaccount.google.com/security)

**Passo 2:** Ative a **Verificação em duas etapas** (obrigatório para Senhas de App)

**Passo 3:** Acesse [myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords)

**Passo 4:** Crie uma Senha de App:
- Selecionar app: **Outro (nome personalizado)**
- Nome: `Brasallis Hub`
- Clique em **Gerar**
- Copie a senha de 16 caracteres exibida

**Passo 5:** Preencha no `.env`:

```env
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=seuemail@gmail.com
MAIL_PASSWORD=abcd efgh ijkl mnop    # ← Senha de App gerada (sem espaços)
MAIL_FROM_ADDRESS=noreply@brasallis.pro
MAIL_FROM_NAME="Brasallis Hub"
```

### 3.2 — Opção B: SendGrid (recomendado para produção em escala)

**Passo 1:** Crie conta em [sendgrid.com](https://sendgrid.com) (plano gratuito: 100 e-mails/dia)

**Passo 2:** Acesse **Settings → API Keys → Create API Key**

**Passo 3:** Selecione **Restricted Access → Mail Send → Full Access**

**Passo 4:** Copie a API Key gerada

**Passo 5:** Preencha no `.env`:

```env
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey                  # ← Literal "apikey" mesmo
MAIL_PASSWORD=SG.xxxxxxxxxxxxxxxx     # ← Sua API Key do SendGrid
MAIL_FROM_ADDRESS=noreply@brasallis.pro
MAIL_FROM_NAME="Brasallis Hub"
```

> [!IMPORTANT]
> O domínio do `MAIL_FROM_ADDRESS` deve ser verificado no SendGrid (Settings → Sender Authentication).
> E-mails enviados de domínios não verificados caem no spam.

### 3.3 — Testar o envio de e-mail

Após configurar, teste acessando:
```
https://seusite.com/esqueceu_senha.php
```
Digite um e-mail cadastrado e verifique se o link chega na caixa de entrada.

---

## 4. Login com Google (OAuth 2.0)

### 4.1 — Criar projeto no Google Cloud

**Passo 1:** Acesse [console.cloud.google.com](https://console.cloud.google.com)

**Passo 2:** Clique em **Selecionar projeto → Novo Projeto**
- Nome: `Brasallis Hub`
- Clique em **Criar**

**Passo 3:** No menu lateral, vá em **APIs e serviços → Tela de consentimento OAuth**
- Tipo de usuário: **Externo**
- Clique em **Criar**

**Passo 4:** Preencha os dados da tela de consentimento:
- Nome do app: `Brasallis Hub`
- E-mail de suporte: seu e-mail
- Logotipo: opcional
- Clique em **Salvar e continuar**

**Passo 5:** Em **Escopos**, clique em **Adicionar ou remover escopos** e adicione:
- `openid`
- `email`
- `profile`

**Passo 6:** Em **Usuários de teste**, adicione os e-mails autorizados durante desenvolvimento

### 4.2 — Criar credenciais OAuth 2.0

**Passo 1:** Vá em **APIs e serviços → Credenciais → Criar credenciais → ID do cliente OAuth**

**Passo 2:** Tipo de aplicativo: **Aplicativo Web**

**Passo 3:** Configure as URIs:

| Campo | Valor |
|---|---|
| **Origens JavaScript autorizadas** | `https://seudominio.com.br` |
| **URIs de redirecionamento autorizados** | `https://seudominio.com.br/auth/google-callback.php` |

> [!WARNING]
> Em desenvolvimento, adicione também:
> - Origem: `http://localhost:8001`
> - Redirect URI: `http://localhost:8001/auth/google-callback.php`

**Passo 4:** Clique em **Criar** e copie:
- **ID do cliente** (termina em `.apps.googleusercontent.com`)
- **Chave secreta do cliente**

### 4.3 — Configurar no `.env`

```env
GOOGLE_CLIENT_ID=123456789-xxxxxxxxxxxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-xxxxxxxxxxxxxxxxxx
GOOGLE_REDIRECT_URI=https://seudominio.com.br/auth/google-callback.php
```

### 4.4 — Publicar o app no Google (para uso externo)

Enquanto o app estiver em modo **Teste**, apenas os e-mails adicionados como "usuários de teste" conseguem fazer login.

Para liberar para todos:
1. Vá em **APIs → Tela de consentimento OAuth**
2. Clique em **Publicar app**
3. Aguarde verificação do Google (pode levar alguns dias para apps com escopos sensíveis)

> [!NOTE]
> Para uso interno da empresa (todos com o mesmo domínio de e-mail), você pode usar o **Google Workspace** em vez de OAuth público, com configuração simplificada.

---

## 5. Chave Mestre e Scripts de Manutenção

A `MASTER_KEY` protege os scripts de diagnóstico e manutenção do sistema.

### 5.1 — Gerar uma chave forte

```bash
# Linux / Mac / WSL
openssl rand -hex 32

# PowerShell (Windows)
[System.BitConverter]::ToString([System.Security.Cryptography.RandomNumberGenerator]::GetBytes(32)).Replace('-','').ToLower()
```

Exemplo de output: `a3f8c2e1d7b4906e5f2a1c8d3b6e9f0a7d4c2b1e8f5a3d6c9b0e7f4a2d1c8b5`

### 5.2 — Configurar no `.env`

```env
MASTER_KEY=a3f8c2e1d7b4906e5f2a1c8d3b6e9f0a7d4c2b1e8f5a3d6c9b0e7f4a2d1c8b5
```

### 5.3 — Usar os scripts de manutenção

Os scripts protegidos (`diagnose_db.php`, `fix_db.php`, etc.) agora exigem autenticação via header HTTP:

```bash
# Exemplo com curl
curl -H "Authorization: Bearer SUA_MASTER_KEY" \
     https://seudominio.com.br/diagnose_db.php

# Com Insomnia / Postman
# Header: Authorization
# Value: Bearer SUA_MASTER_KEY
```

> [!CAUTION]
> **Nunca** passe a MASTER_KEY via URL (`?key=...`). Isso expõe a chave nos logs do servidor.
> Use **sempre** o header `Authorization: Bearer`.

---

## 6. Segurança: Checklist Final para Produção

Execute este checklist antes de abrir o sistema ao público:

### Ambiente
- [ ] `APP_ENV=production` no `.env` do servidor
- [ ] `APP_DEBUG=false` no `.env` do servidor
- [ ] `APP_URL` apontando para o domínio real com HTTPS

### Banco de Dados
- [ ] Usuário MySQL dedicado criado (não root)
- [ ] Senha forte no banco (mínimo 20 caracteres, aleatória)
- [ ] Migration `2026_05_04_google_oauth_reset.sql` executada
- [ ] Backup automático configurado (diário)

### E-mail
- [ ] SMTP configurado e testado (link de reset chega na caixa de entrada)
- [ ] `MAIL_FROM_ADDRESS` com domínio verificado no provedor

### Google OAuth
- [ ] `GOOGLE_CLIENT_ID` e `GOOGLE_CLIENT_SECRET` preenchidos
- [ ] URI de redirect cadastrada no Google Cloud Console
- [ ] App publicado no Google (se uso externo)

### Segurança Geral
- [ ] `MASTER_KEY` forte gerada com `openssl rand -hex 32`
- [ ] SSL/HTTPS ativo no domínio (certificado Let's Encrypt ou similar)
- [ ] Header HSTS ativado no `.htaccess` (descomentar a linha `Strict-Transport-Security`)
- [ ] Arquivos de debug não acessíveis sem MASTER_KEY
- [ ] `.env` **não** está no repositório Git (verificar com `git status`)

### Verificação Final
```bash
# Confirmar que .env não está no git
git ls-files --error-unmatch .env
# Deve retornar erro (significa que está no .gitignore ✓)
```

---

## Suporte

Em caso de dúvidas, acesse o painel do SuperAdmin em:
```
https://seudominio.com.br/superadmin/index.php
```
E verifique os **Logs do Sistema** para diagnósticos em tempo real.
