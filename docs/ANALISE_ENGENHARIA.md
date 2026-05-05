# Documento de Engenharia de Software: Gerenciador de Estoque (Análise Aprofundada)

**Versão:** 3.0 (Gerado por IA após análise de código)
**Data:** 28/09/2025

## 1. Visão Geral do Sistema

### 1.1. Objetivo
O "Gerenciador de Estoque" é um sistema web monolítico desenvolvido em PHP procedural, projetado para oferecer uma solução de controle de produtos, estoque, compras e fornecedores. O sistema visa fornecer uma experiência de usuário funcional e informativa, com dashboards, relatórios e um sistema de notificação proativo, incluindo a extração de dados de notas fiscais via IA.

### 1.2. Escopo e Funcionalidades Principais
*   **Controle de Acesso:** Autenticação de usuários com dois níveis de permissão (administrador, funcionário), CRUD de usuários e um fluxo de recuperação de senha.
*   **Gestão de Entidades:** CRUDs completos para Produtos, Fornecedores, Compras e Usuários.
*   **Processamento de Compras com IA:** Capacidade de anexar uma nota fiscal (PDF/imagem) e invocar um script Python para extrair e salvar os dados da nota.
*   **Operações de Estoque:** Entradas e saídas de estoque com registro de histórico para auditoria.
*   **Dashboards e Relatórios:** Visualização de dados estratégicos e operacionais com métricas e gráficos.
*   **Notificações Automatizadas:** Alertas para estoque baixo e produtos próximos ao vencimento.

## 2. Arquitetura de Software

### 2.1. Visão Geral da Arquitetura
O sistema utiliza uma arquitetura **monolítica procedural**. A lógica de negócio, a renderização de HTML e o acesso a dados são fortemente acoplados, frequentemente contidos nos mesmos scripts PHP (ex: `admin/produtos.php`).

*   **Padrão de Acesso:** O servidor web (Apache/Nginx) executa diretamente o script PHP correspondente à URL requisitada. Não há um ponto de entrada único (Front Controller).
*   **Comunicação Cliente-Servidor:** A interação primária é baseada em requisições síncronas (submissão de formulários) seguindo o padrão **Post-Redirect-Get (PRG)**, o que é uma boa prática para evitar o reenvio de dados. Requisições assíncronas (AJAX) são usadas para popular modais de edição, buscando dados de endpoints na pasta `/api`.
*   **Integração Externa (IA):** Para tarefas de IA, o sistema delega a execução a um script Python externo (`scripts/process_invoice.py`) chamado em segundo plano, uma abordagem eficaz para não bloquear a interface do usuário.

### 2.2. Tecnologias Utilizadas
*   **Backend:**
    *   PHP 7.4+ com a extensão **PDO**.
    *   **Python** para scripts de automação e IA.
*   **Banco de Dados:** **MySQL** (utilizando o motor InnoDB).
*   **Frontend:** HTML5, CSS3, JavaScript.
*   **Bibliotecas Frontend:** Bootstrap 5, Font Awesome, Chart.js.
*   **Gerenciador de Dependências:** **Composer** está configurado (`composer.json`), mas atualmente **não gerencia nenhuma dependência externa**. A configuração de autoload PSR-4 para um diretório `src/` existe, mas não está em uso, indicando uma oportunidade para refatoração.

### 2.3. Estrutura de Diretórios
A estrutura de diretórios é modular e separa as responsabilidades por funcionalidade de alto nível.

```
/
├── admin/                  # Módulo administrativo (CRUDs, relatórios)
├── api/                    # Endpoints de API (AJAX) - [PONTO DE ATENÇÃO]
├── assets/                 # Arquivos estáticos (CSS, JS, Imagens)
├── employee/               # Módulo do funcionário (operações de estoque)
├── includes/               # Arquivos PHP reutilizáveis (config, funções, header/footer)
├── scripts/                # Scripts de automação (Python)
├── uploads/                # Diretório para uploads (notas fiscais)
├── index.php               # Página de login (View)
├── login.php               # Lógica de autenticação
├── configurar_banco_dados.php # Script de setup do banco de dados
└── ... outros arquivos de fluxo
```

## 3. Modelo de Dados (Banco de Dados)

O esquema do banco de dados, definido em `configurar_banco_dados.php`, é robusto e bem estruturado, utilizando chaves estrangeiras para garantir a integridade referencial. As 9 tabelas (`usuarios`, `produtos`, `fornecedores`, `compras`, `itens_compra`, `dados_nota_fiscal`, `historico_estoque`, `redefinicoes_senha`, `notificacoes`) refletem com precisão as necessidades do sistema.

## 4. Análise de Fluxos de Lógica

### 4.1. Fluxo de Autenticação
1.  O usuário submete o formulário em `index.php` para `login.php`.
2.  `login.php` sanitiza os inputs, busca o usuário no banco e verifica a senha usando `password_verify()`.
3.  Se a verificação for bem-sucedida, os dados do usuário são armazenados em `$_SESSION`.
4.  O usuário é redirecionado para o painel correspondente (`admin` ou `employee`).

### 4.2. Fluxo de CRUD (Ex: Produtos)
1.  A página `admin/produtos.php` busca e exibe a lista de produtos.
2.  Ações de Adicionar, Editar e Deletar são feitas através de modais do Bootstrap.
3.  Os formulários dos modais submetem os dados via POST para o próprio arquivo `admin/produtos.php`.
4.  A lógica no topo do arquivo processa a requisição (INSERT, UPDATE, DELETE) e redireciona para a mesma página, seguindo o padrão PRG.
5.  Para editar, um script JavaScript busca os dados atuais do produto via `fetch` no endpoint `api/get_product.php` para preencher o modal.

## 5. Análise Crítica

### 5.1. Pontos Fortes
*   **Segurança de Dados:** O sistema previne **SQL Injection** de forma eficaz através do uso consistente de **PDO com Prepared Statements**. A segurança de senhas é robusta, utilizando `password_hash()` e `password_verify()`.
*   **Integridade do Banco de Dados:** O uso do motor `InnoDB` e a definição explícita de `FOREIGN KEY`s garantem a consistência e a integridade dos dados.
*   **Experiência do Usuário:** A implementação do padrão **Post-Redirect-Get** e o uso de AJAX para carregamento de dados em modais proporcionam uma experiência de navegação fluida e sem reenvios acidentais de formulário.
*   **Programação Defensiva de Estoque:** O sistema utiliza transações e a cláusula `FOR UPDATE` em operações críticas de estoque manual, prevenindo condições de corrida e garantindo a consistência dos dados.
*   **Documentação e Organização:** O projeto possui uma documentação técnica clara e uma estrutura de arquivos e banco de dados lógica e bem definida.

### 5.2. Pontos Fracos e Riscos
1.  **FALHA DE SEGURANÇA (CRÍTICA): API Desprotegida (CORRIGIDO).**
    *   **Observação:** A análise original apontou esta falha. Uma verificação posterior confirmou que **o problema foi resolvido**. Todos os endpoints na pasta `/api` agora implementam a verificação de sessão (`$_SESSION['user_id']`) e filtram todas as consultas pelo `empresa_id` do usuário logado, garantindo o isolamento de dados e prevenindo o acesso não autorizado.

2.  **Débito Técnico (Alto): Código Fortemente Acoplado.**
    *   **Risco:** Arquivos como `admin/produtos.php` misturam lógica de banco de dados, renderização de HTML e JavaScript.
    *   **Impacto:** Isso torna a manutenção difícil, aumenta a duplicação de código (ex: a conexão com o banco é repetida em vários arquivos) e dificulta a implementação de testes automatizados.

3.  **Débito Técnico (Médio): Ausência de Padrões Modernos.**
    *   **Risco:** A falta de um **autoloader** (o do Composer não está em uso) e de um **roteador** (Front Controller) obriga o acesso direto a arquivos `.php` e o uso de `require_once`.
    *   **Impacto:** A arquitetura é menos segura (arquivos de lógica podem ser acessados diretamente pela URL) e mais difícil de escalar. A refatoração para Orientação a Objetos (OOP) é bloqueada por essa estrutura.

4.  **Inconsistência de Dados: Categorias como Texto Livre (CORRIGIDO).**
    *   **Observação:** A análise original apontou este risco. Uma verificação posterior do código e do banco de dados (`configurar_banco_dados.php`) confirmou que **o problema foi resolvido**. O sistema agora utiliza uma tabela `categorias` dedicada e os formulários de produto usam um campo `<select>` para garantir a consistência dos dados.

## 6. Plano de Ação Recomendado

A seguir, uma lista priorizada de ações para corrigir os problemas e evoluir a arquitetura do sistema.

### Prioridade 1: Correção de Segurança (Crítico)
*   **Ação:** Adicionar verificação de sessão em **todos** os arquivos dentro da pasta `/api`.
*   **Implementação Sugerida:** No início de cada script da API, adicionar o seguinte bloco de código:
    ```php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        http_response_code(401); // Unauthorized
        echo json_encode(['error' => 'Acesso não autorizado.']);
        exit;
    }
    ```

### Prioridade 2: Melhoria Funcional (Rápido Ganho)
*   **Ação:** Criar um CRUD dedicado para `Categorias`.
*   **Implementação Sugerida:**
    1.  Criar uma nova tabela `categorias` no banco de dados.
    2.  Substituir o campo de texto livre de categoria nos formulários de produto por um `<select>` populado a partir da nova tabela.
    3.  Criar a página `admin/categorias.php` para gerenciar as categorias.

### Prioridade 3: Refatoração para OOP (Débito Técnico)
*   **Ação:** Iniciar a transição do código procedural para Orientação a Objetos.
*   **Implementação Sugerida (Passo a Passo):**
    1.  Criar o diretório `src/` como definido no `composer.json`.
    2.  Criar uma classe `App\Database` que gerencia a conexão PDO, para que a conexão não seja mais recriada em múltiplos arquivos.
    3.  Começar a criar classes de Repositório (ex: `App\ProdutoRepository`) que encapsulam toda a lógica de SQL para a entidade `produtos`.
    4.  Refatorar `admin/produtos.php` para usar `ProdutoRepository` em vez de executar SQL diretamente.

### Prioridade 4: Melhoria de Arquitetura (Longo Prazo)
*   **Ação:** Implementar um Roteador e o padrão Front Controller.
*   **Implementação Sugerida:**
    1.  Adicionar uma biblioteca de roteamento via Composer (ex: `nikic/fast-route`).
    2.  Criar um `index.php` na raiz que atue como Front Controller, recebendo todas as requisições.
    3.  Mapear URLs amigáveis (ex: `/admin/produtos`) para funções ou métodos de Controller, eliminando o acesso direto a arquivos `.php`.

---

## 7. Verificação de Conformidade (Outubro 2025)

**Análise realizada por:** Agente de IA (Gemini)
**Data:** 03/10/2025

Uma nova análise foi conduzida para verificar o estado atual do sistema em relação a esta documentação.

### 7.1. Verificação de Segurança
*   **Status:** CONFIRMADO.
*   **Observação:** Foi verificado que **todos** os endpoints na pasta `/api` implementam corretamente a verificação de sessão de usuário (`$_SESSION['user_id']`) e o filtro de dados por `empresa_id`. A falha de segurança crítica de acesso não autorizado à API, mencionada na análise original, foi **efetivamente corrigida**.

### 7.2. Validade da Análise Arquitetural
*   **Status:** CONFIRMADO.
*   **Observação:** Os pontos fracos e débitos técnicos identificados (código fortemente acoplado, ausência de padrões modernos) continuam válidos e representam as principais oportunidades de melhoria para o projeto. O plano de ação recomendado permanece relevante.

---

## 8. Verificação de Conformidade (Outubro 2025 - Rodada 2)

**Análise realizada por:** Agente de IA (Gemini)
**Data:** 06/10/2025

Uma terceira análise foi conduzida para documentar as melhorias recentes na funcionalidade de IA e na interface.

### 8.1. Melhorias no Agente de IA
*   **Status:** IMPLEMENTADO.
*   **Observação:** O script de processamento de notas fiscais (`scripts/process_invoice.py`) foi significativamente aprimorado. Um novo prompt, baseado em boas práticas de engenharia de prompt (Persona, Contexto, Exemplos, Restrições), foi implementado. O agente agora é "universal", projetado para extrair campos tanto de notas fiscais de produto (NF-e) quanto de serviço (NFS-e), tratando campos ausentes de forma robusta.

### 8.2. Melhorias na Interface (UI/UX)
*   **Status:** IMPLEMENTADO.
*   **Observação:** A página de `admin/compras.php` foi atualizada para incluir um modal de "Detalhes da IA". Para cada compra processada, o usuário pode agora clicar em um botão para visualizar todos os dados extraídos pela IA, bem como a própria imagem da nota fiscal, facilitando a verificação e validação dos dados.

### 8.3. Validade da Análise Arquitetural
*   **Status:** INALTERADO.
*   **Observação:** As observações sobre o débito técnico (código acoplado, ausência de padrões modernos) e as recomendações de longo prazo (refatoração para OOP, implementação de roteador) continuam válidas e são os próximos passos lógicos para a evolução da arquitetura do sistema.
