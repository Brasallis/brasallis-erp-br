# Arquitetura do Sistema: Gestor Inteligente

Este documento descreve como o sistema **Gestor Inteligente** funciona, detalhando as tecnologias utilizadas e como os diferentes componentes (Frontend, Backend e Banco de Dados) "conversam" entre si para entregar as funcionalidades de gestão de estoque e vendas.

## 1. Visão Geral das Tecnologias (Tech Stack)

O sistema foi construído utilizando uma arquitetura robusta e amplamente adotada no mercado, garantindo performance e facilidade de manutenção.

### **Frontend (A Interface Visual)**
É a parte do sistema com a qual o usuário interage.
*   **HTML5 & CSS3:** Estrutura e estilização das páginas.
*   **Bootstrap 5:** Framework visual para garantir que o sistema seja responsivo (funcione em celulares e computadores) e tenha uma aparência moderna e profissional.
*   **JavaScript (Vanilla & AJAX):** Responsável pela interatividade. O JavaScript "conversa" com o Backend sem precisar recarregar a página (ex: ao buscar um produto no PDV ou abrir um modal de edição).
*   **Chart.js:** Biblioteca utilizada para gerar os gráficos interativos e animados nos dashboards.

### **Backend (O Cérebro)**
É onde as regras de negócio são processadas.
*   **PHP 7.4+:** Linguagem de programação principal. O PHP recebe as requisições do Frontend, processa as informações (ex: calcula totais, verifica estoque) e decide o que fazer com elas.
*   **Python:** Utilizado para tarefas de Inteligência Artificial, especificamente para ler e extrair dados de notas fiscais (PDF/Imagens).
*   **Arquitetura MVC (Adaptada):** O sistema segue princípios de separação de responsabilidades, onde scripts PHP específicos lidam com requisições, processamento e acesso a dados.

### **Banco de Dados (A Memória)**
Onde todas as informações são guardadas de forma segura.
*   **MySQL:** Sistema gerenciador de banco de dados relacional. É aqui que ficam salvos os usuários, produtos, vendas, histórico, etc.

---

## 2. Como o Sistema "Conversa" (Fluxo de Dados)

O funcionamento do sistema baseia-se em um ciclo constante de **Requisição (Request)** e **Resposta (Response)**.

### **Fluxo 1: Carregamento de Página (Ex: Dashboard)**
1.  **Usuário:** Acessa `painel_admin.php`.
2.  **Frontend:** O navegador pede essa página ao servidor.
3.  **Backend (PHP):**
    *   Verifica se o usuário está logado (Segurança).
    *   Conecta ao **Banco de Dados (MySQL)**.
    *   Solicita os dados: "Me dê o total de vendas de hoje" ou "Quais produtos estão com estoque baixo?".
4.  **Banco de Dados:** Retorna os números brutos.
5.  **Backend:** Organiza esses dados e os insere no HTML.
6.  **Frontend:** Recebe o HTML pronto e exibe a página bonita para o usuário.

### **Fluxo 2: Interação Dinâmica (Ex: Busca no PDV)**
1.  **Usuário:** Digita "Coca" no campo de busca do PDV.
2.  **Frontend (JavaScript):** Detecta a digitação e envia uma mensagem silenciosa (AJAX) para o Backend (`api/search_products.php`).
3.  **Backend (PHP):**
    *   Recebe o termo "Coca".
    *   Pergunta ao **Banco de Dados**: "Tem algum produto com nome parecido com 'Coca'?".
4.  **Banco de Dados:** Retorna: "Sim, tem 'Coca Cola 2L' e 'Coca Cola Lata'".
5.  **Backend:** Transforma essa lista em um formato leve (JSON) e devolve para o Frontend.
6.  **Frontend:** Recebe a lista e desenha as opções na tela instantaneamente, sem recarregar a página.

---

## 3. Estrutura do Banco de Dados

O banco de dados foi projetado para ser **Multi-tenant**, ou seja, várias empresas podem usar o sistema, mas os dados de uma empresa são invisíveis para as outras.

### **Principais Tabelas**
*   **`empresas`**: A tabela "mãe". Cada registro aqui é um cliente diferente.
*   **`usuarios`**: Quem acessa o sistema. Cada usuário está vinculado a uma `empresa_id`.
*   **`produtos`**: O catálogo de itens. Contém preço, custo, quantidade e estoque mínimo.
*   **`vendas` & `venda_itens`**: Registram cada venda. A tabela `vendas` guarda o total e o cliente; a `venda_itens` guarda quais produtos foram levados.
*   **`historico_estoque`**: O "dedo-duro". Registra cada movimentação (entrada ou saída), quem fez, quando e por quê. É essencial para auditoria.

---

## 4. Diferenciais Técnicos

### **Inteligência Artificial (Python + PHP)**
Quando você sobe uma Nota Fiscal:
1.  O **PHP** salva o arquivo.
2.  O PHP chama um script **Python** em segundo plano.
3.  O **Python** usa bibliotecas de visão computacional e IA para "ler" o PDF/Imagem.
4.  Ele extrai: Número da Nota, Data, Fornecedor e Produtos.
5.  Devolve os dados estruturados para o PHP salvar no Banco.

### **Segurança**
*   **Senhas Criptografadas:** As senhas nunca são salvas em texto puro. Usamos `password_hash` (Bcrypt).
*   **Isolamento de Dados:** Todo comando no banco de dados (SELECT, INSERT, UPDATE) obrigatoriamente inclui `WHERE empresa_id = X`. Isso garante que uma empresa nunca veja dados de outra.
