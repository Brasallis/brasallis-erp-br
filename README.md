# Brasallis ERP - Gestão Inteligente na Nuvem ☁️

[![PHP 8.0+](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-Database-blue.svg)](https://mysql.com)
[![License](https://img.shields.io/badge/License-Proprietary-red.svg)]()
[![Status](https://img.shields.io/badge/Status-Production_Ready-success.svg)]()

Bem-vindo ao repositório oficial do **Brasallis ERP**, um sistema de gestão corporativa (SaaS) projetado para alto desempenho, segurança e operação Multi-Tenant (Múltiplas Empresas).

## 🚀 Módulos Principais

*   💳 **Frente de Caixa (PDV):** Interface ultra-rápida ("Fast Checkout"), com suporte a leitura de código de barras e atalhos de teclado.
*   📦 **Gestão de Estoque:** Controle preciso de inventário, produtos, categorias e alertas de baixo estoque.
*   📊 **Dashboard Executivo:** Relatórios completos de faturamento, tickets médios e estatísticas por período.
*   👥 **Controle de Acesso Funcional (RBAC):** Isolamento total. Administradores enxergam a operação global, enquanto funcionários (caixas) acessam apenas suas próprias movimentações.
*   🔒 **Cloud Security First:**
    *   Uploads bloqueados via `.htaccess` (Prevenção contra scripts maliciosos).
    *   Arquitetura 100% Multi-Tenant (Cada query no banco valida a empresa correspondente).
    *   Gerenciamento avançado de senhas (bcrypt/AES-256) e tokens.

## 🛠 Arquitetura e Tecnologias

O sistema foi desenvolvido utilizando a robustez e estabilidade do **PHP Puro (PDO)**, sem amarras a frameworks engessados (Zero Vendor Lock-in).

*   **Linguagem:** PHP 8+
*   **Banco de Dados:** MySQL / MariaDB
*   **Interface (Front-end):** HTML5, CSS3 Premium, Vanilla Javascript
*   **Gerenciador de Pacotes:** Composer (para Dotenv e PHPMailer)

## 📦 Como Instalar (Ambiente de Desenvolvimento)

1. Clone o repositório:
```bash
git clone https://github.com/Brasallis/brasallis-erp-br.git
cd brasallis-erp-br
```

2. Instale as dependências via Composer:
```bash
composer install
```

3. Crie e configure o ambiente:
Renomeie `.env.example` para `.env` e preencha as credenciais do seu banco de dados.
```env
APP_ENV=local
APP_DEBUG=true
DB_HOST=localhost
DB_NAME=brasallis_db
DB_USER=root
DB_PASS=
```

4. Acesse o sistema!

---
© 2026 Brasallis Hub. Todos os direitos reservados.
