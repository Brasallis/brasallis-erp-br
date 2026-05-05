# Projeto de Banco de Dados - Físico

O modelo físico é a etapa final e trata da implementação concreta do banco de dados no Sistema Gerenciador de Banco de Dados (SGBD) escolhido. Para o sistema "Gerenciador de Estoque", o SGBD selecionado foi o **MySQL** (ou MariaDB), devido à sua robustez, escalabilidade e ampla compatibilidade com a stack tecnológica (PHP/Apache).

Este documento detalha as decisões técnicas tomadas para garantir eficiência, integridade e segurança dos dados, considerando o volume esperado e a arquitetura Multi-tenant.

## 1. Scripts SQL de Criação (DDL)

A estrutura física é definida através de scripts DDL (Data Definition Language). Abaixo estão os comandos essenciais que materializam o esquema no servidor MySQL.

### Configuração Inicial
```sql
CREATE DATABASE IF NOT EXISTS gerenciador_estoque;
USE gerenciador_estoque;
SET FOREIGN_KEY_CHECKS = 0; -- Permite recriação de tabelas sem travas
```

### Tabelas Principais (Exemplos)

**Empresas (Tenant)**
```sql
CREATE TABLE IF NOT EXISTS empresas (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    owner_user_id INT(11) UNSIGNED NOT NULL,
    cnpj VARCHAR(20) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
```

**Produtos**
```sql
CREATE TABLE IF NOT EXISTS produtos (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT(11) UNSIGNED NOT NULL,
    categoria_id INT(11) UNSIGNED NULL,
    name VARCHAR(255) NOT NULL,
    sku VARCHAR(50) NULL, -- Índice recomendado para buscas rápidas
    price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    quantity INT(11) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

**Dados de Nota Fiscal (Integração IA)**
```sql
CREATE TABLE IF NOT EXISTS dados_nota_fiscal (
    compra_id INT(11) UNSIGNED NOT NULL PRIMARY KEY,
    status ENUM('pendente', 'processado', 'erro') NOT NULL DEFAULT 'pendente',
    itens_json TEXT NULL, -- Armazena estrutura complexa retornada pela IA
    FOREIGN KEY (compra_id) REFERENCES compras(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

## 2. Definição de Tipos de Dados

A escolha dos tipos de dados foi otimizada para precisão e economia de espaço:

*   **`INT(11) UNSIGNED`**: Utilizado para todas as Chaves Primárias (PK) e Estrangeiras (FK). O atributo `UNSIGNED` dobra a capacidade de IDs positivos (até 4.2 bilhões), essencial para tabelas de alto crescimento como `historico_estoque`.
*   **`DECIMAL(10, 2)`**: Obrigatório para valores monetários (`price`, `total_amount`). Diferente do `FLOAT`, o `DECIMAL` garante precisão exata, evitando erros de arredondamento em cálculos financeiros.
*   **`ENUM`**: Utilizado em colunas de estado finito (`user_type`, `status`, `action`). Ocupa menos espaço que `VARCHAR` e garante integridade de domínio na própria camada de banco.
*   **`TIMESTAMP`**: Preferido sobre `DATETIME` para colunas de auditoria (`created_at`), pois normaliza o fuso horário (UTC) automaticamente.

## 3. Integridade e Gatilhos (Triggers/Transactions)

Para garantir a consistência dos dados, o projeto utiliza uma abordagem híbrida de chaves estrangeiras e transações na aplicação.

*   **Integridade Referencial (Foreign Keys):**
    *   `ON DELETE CASCADE`: Configurado em tabelas dependentes (ex: `itens_compra`, `historico_estoque`). Se uma empresa ou compra for excluída, todos os registros relacionados são removidos automaticamente pelo banco, evitando dados órfãos.
    *   `ON DELETE RESTRICT`: Configurado em tabelas críticas (ex: `vendas` -> `usuarios`). Impede a exclusão acidental de um usuário que possui vendas registradas.

*   **Transações (ACID):**
    *   Em vez de *Triggers* complexos no banco (que podem ocultar regras de negócio), a integridade de operações compostas é garantida via transações `PDO` no PHP.
    *   **Exemplo:** No registro de uma compra, a inserção na tabela `compras`, `itens_compra` e a atualização na tabela `produtos` ocorrem dentro de um bloco `beginTransaction()` / `commit()`. Se qualquer etapa falhar, o `rollBack()` reverte tudo.

## 4. Estratégias de Otimização

Considerando o acesso frequente e o modelo multi-tenant:

*   **Índices Compostos:** Embora não explicitados no script básico, recomenda-se a criação de índices compostos em `(empresa_id, sku)` na tabela `produtos`. Isso acelera drasticamente as buscas, pois o banco filtra primeiro a empresa e depois o produto, sem varrer a tabela inteira.
*   **Isolamento Lógico:** Todas as consultas (`SELECT`) incluem obrigatoriamente a cláusula `WHERE empresa_id = ?`. Isso permite que o MySQL utilize o índice da chave estrangeira para limitar o escopo da busca instantaneamente.
*   **Paginação:** Consultas de listagem utilizam `LIMIT` e `OFFSET`, reduzindo a carga de memória no servidor e o tráfego de rede para dispositivos móveis.

## 5. Armazenamento de Dados Multimídia

Para evitar o inchaço do banco de dados e garantir performance:

*   **Filesystem Storage:** Arquivos binários pesados (PDFs de notas fiscais, imagens) **não** são salvos no banco (BLOB). Eles são armazenados no sistema de arquivos do servidor (`/uploads/`).
*   **Referência no Banco:** A tabela `compras` armazena apenas o caminho relativo (`fiscal_note_path` como `VARCHAR`).
*   **Vantagens:** Mantém o backup do banco leve e permite que o servidor web (Apache/Nginx) sirva os arquivos estáticos com cache otimizado, sem passar pelo processamento do banco.

## 6. Esquemas de Backup e Recuperação

*   **Backup Lógico (`mysqldump`):** Devido ao tamanho moderado esperado, a estratégia principal é o dump diário da estrutura e dados.
    *   Comando: `mysqldump -u user -p --single-transaction --quick gerenciador_estoque > backup_YYYYMMDD.sql`
    *   A opção `--single-transaction` garante que o backup seja feito sem travar as tabelas InnoDB durante a operação.
*   **Backup de Arquivos:** A pasta `/uploads` deve ser incluída na rotina de backup incremental do servidor, pois contém os documentos fiscais que não estão no SQL.
