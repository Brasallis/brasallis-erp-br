# Modelo Lógico do Banco de Dados - Gerenciador de Estoque

Este documento detalha a estrutura lógica do banco de dados, especificando tipos de dados, chaves primárias (PK), chaves estrangeiras (FK) e restrições.

**SGBD:** MySQL / MariaDB
**Engine:** InnoDB
**Charset:** UTF-8 (implícito)

---

## 1. Tabelas de Sistema e Multi-tenancy

### `empresas`
Tabela raiz para o isolamento de dados (Multi-tenant).
| Coluna | Tipo | Atributos | Descrição |
|---|---|---|---|
| `id` | INT(11) | **PK**, AI, UNSIGNED | Identificador único da empresa. |
| `name` | VARCHAR(255) | NOT NULL | Razão social ou nome fantasia. |
| `owner_user_id` | INT(11) | UNSIGNED, NOT NULL | ID do usuário proprietário da conta. |
| `address` | TEXT | NULL | Endereço completo. |
| `phone` | VARCHAR(50) | NULL | Telefone de contato. |
| `email` | VARCHAR(100) | NULL | E-mail de contato da empresa. |
| `cnpj` | VARCHAR(20) | NULL | Cadastro Nacional de Pessoa Jurídica. |
| `website` | VARCHAR(255) | NULL | Site da empresa. |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Data de cadastro. |

### `usuarios`
Usuários com acesso ao sistema.
| Coluna | Tipo | Atributos | Descrição |
|---|---|---|---|
| `id` | INT(11) | **PK**, AI, UNSIGNED | Identificador único do usuário. |
| `empresa_id` | INT(11) | **FK**, UNSIGNED, NOT NULL | Empresa à qual o usuário pertence. |
| `username` | VARCHAR(50) | NOT NULL | Nome de exibição. |
| `password` | VARCHAR(255) | NOT NULL | Hash da senha. |
| `email` | VARCHAR(100) | UNIQUE, NOT NULL | E-mail de login (único globalmente). |
| `user_type` | ENUM | 'admin', 'employee' | Nível de permissão. |
| `plan` | VARCHAR(50) | DEFAULT 'basico' | Plano de assinatura. |
| `trial_ends_at` | DATETIME | NULL | Data fim do período de teste. |
| `subscription_status`| VARCHAR(50) | DEFAULT 'active' | Status da assinatura. |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Data de criação. |

---

## 2. Cadastros Principais

### `categorias`
Categorização de produtos.
| Coluna | Tipo | Atributos | Descrição |
|---|---|---|---|
| `id` | INT(11) | **PK**, AI, UNSIGNED | Identificador único. |
| `empresa_id` | INT(11) | **FK**, UNSIGNED, NOT NULL | Empresa proprietária. |
| `nome` | VARCHAR(255) | NOT NULL | Nome da categoria. |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Data de criação. |

### `fornecedores`
Parceiros de fornecimento.
| Coluna | Tipo | Atributos | Descrição |
|---|---|---|---|
| `id` | INT(11) | **PK**, AI, UNSIGNED | Identificador único. |
| `empresa_id` | INT(11) | **FK**, UNSIGNED, NOT NULL | Empresa proprietária. |
| `name` | VARCHAR(255) | NOT NULL | Nome do fornecedor. |
| `contact_person` | VARCHAR(255) | NULL | Pessoa de contato. |
| `phone` | VARCHAR(50) | NULL | Telefone. |
| `email` | VARCHAR(100) | NULL | E-mail. |
| `address` | TEXT | NULL | Endereço. |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Data de criação. |

### `produtos`
Inventário de itens.
| Coluna | Tipo | Atributos | Descrição |
|---|---|---|---|
| `id` | INT(11) | **PK**, AI, UNSIGNED | Identificador único. |
| `empresa_id` | INT(11) | **FK**, UNSIGNED, NOT NULL | Empresa proprietária. |
| `categoria_id` | INT(11) | **FK**, UNSIGNED, NULL | Categoria do produto. |
| `fornecedor_id` | INT(11) | **FK**, UNSIGNED, NULL | Fornecedor principal (opcional). |
| `name` | VARCHAR(255) | NOT NULL | Nome do produto. |
| `sku` | VARCHAR(50) | NULL | Código de referência (Stock Keeping Unit). |
| `description` | TEXT | NULL | Descrição detalhada. |
| `price` | DECIMAL(10,2)| DEFAULT 0.00 | Preço de venda. |
| `cost_price` | DECIMAL(10,2)| DEFAULT 0.00 | Preço de custo. |
| `quantity` | INT(11) | DEFAULT 0 | Quantidade atual em estoque. |
| `minimum_stock` | INT(11) | DEFAULT 0 | Estoque mínimo para alerta. |
| `unidade_medida` | VARCHAR(50) | DEFAULT 'unidade' | Unidade (un, kg, l, etc). |
| `lote` | VARCHAR(255) | NULL | Identificação do lote. |
| `validade` | DATE | NULL | Data de validade. |
| `observacoes` | TEXT | NULL | Observações gerais. |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Data de cadastro. |

---

## 3. Movimentações e Transações

### `compras`
Registro de entradas via compra.
| Coluna | Tipo | Atributos | Descrição |
|---|---|---|---|
| `id` | INT(11) | **PK**, AI, UNSIGNED | Identificador único. |
| `empresa_id` | INT(11) | **FK**, UNSIGNED, NOT NULL | Empresa proprietária. |
| `supplier_id` | INT(11) | **FK**, UNSIGNED, NOT NULL | Fornecedor da compra. |
| `user_id` | INT(11) | **FK**, UNSIGNED, NOT NULL | Usuário que registrou. |
| `purchase_date` | DATE | NOT NULL | Data da compra. |
| `total_amount` | DECIMAL(10,2)| NOT NULL | Valor total da nota. |
| `fiscal_note_path` | VARCHAR(255) | NULL | Caminho do arquivo da nota fiscal. |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Data de registro. |

### `itens_compra`
Detalhes dos produtos em uma compra.
| Coluna | Tipo | Atributos | Descrição |
|---|---|---|---|
| `id` | INT(11) | **PK**, AI, UNSIGNED | Identificador único. |
| `purchase_id` | INT(11) | **FK**, UNSIGNED, NOT NULL | Vínculo com a compra. |
| `product_id` | INT(11) | **FK**, UNSIGNED, NOT NULL | Produto comprado. |
| `quantity` | INT(11) | NOT NULL | Quantidade adquirida. |
| `unit_price` | DECIMAL(10,2)| NOT NULL | Custo unitário nesta compra. |
| `stock_at_purchase`| INT(11) | NULL | Snapshot do estoque antes da entrada. |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Data de registro. |

### `dados_nota_fiscal`
Dados extraídos via IA.
| Coluna | Tipo | Atributos | Descrição |
|---|---|---|---|
| `compra_id` | INT(11) | **PK**, **FK**, UNSIGNED | Vínculo 1:1 com a compra. |
| `status` | ENUM | 'pendente','processado','erro' | Status do processamento. |
| `numero_nota` | VARCHAR(255) | NULL | Número da NF extraído. |
| `data_emissao` | DATE | NULL | Data de emissão extraída. |
| `valor_total` | DECIMAL(10,2)| NULL | Valor total extraído. |
| `nome_fornecedor` | VARCHAR(255) | NULL | Nome do fornecedor extraído. |
| `cnpj_fornecedor` | VARCHAR(50) | NULL | CNPJ extraído. |
| `itens_json` | TEXT | NULL | JSON bruto dos itens. |
| `texto_completo` | TEXT | NULL | Texto completo (OCR) ou mensagem de erro. |
| `raw_ai_response` | TEXT | NULL | Resposta crua da IA (debug). |
| `updated_at` | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP | Última atualização. |

### `vendas`
Registro de saídas.
| Coluna | Tipo | Atributos | Descrição |
|---|---|---|---|
| `id` | INT(11) | **PK**, AI, UNSIGNED | Identificador único. |
| `empresa_id` | INT(11) | **FK**, UNSIGNED, NOT NULL | Empresa proprietária. |
| `user_id` | INT(11) | **FK**, UNSIGNED, NOT NULL | Vendedor. |
| `total_amount` | DECIMAL(10,2)| NOT NULL | Valor total da venda. |
| `payment_method` | VARCHAR(50) | DEFAULT 'dinheiro' | Método de pagamento. |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Data da venda. |

### `venda_itens`
Detalhes dos produtos em uma venda.
| Coluna | Tipo | Atributos | Descrição |
|---|---|---|---|
| `id` | INT(11) | **PK**, AI, UNSIGNED | Identificador único. |
| `venda_id` | INT(11) | **FK**, UNSIGNED, NOT NULL | Vínculo com a venda. |
| `product_id` | INT(11) | **FK**, UNSIGNED, NOT NULL | Produto vendido. |
| `quantity` | INT(11) | NOT NULL | Quantidade vendida. |
| `unit_price` | DECIMAL(10,2)| NOT NULL | Preço unitário de venda. |

### `historico_estoque`
Log de auditoria de movimentações.
| Coluna | Tipo | Atributos | Descrição |
|---|---|---|---|
| `id` | INT(11) | **PK**, AI, UNSIGNED | Identificador único. |
| `empresa_id` | INT(11) | **FK**, UNSIGNED, NOT NULL | Empresa proprietária. |
| `product_id` | INT(11) | **FK**, UNSIGNED, NOT NULL | Produto movimentado. |
| `user_id` | INT(11) | **FK**, UNSIGNED, NOT NULL | Responsável pela ação. |
| `action` | ENUM | 'entrada', 'saida', 'ajuste' | Tipo de movimentação. |
| `quantity` | INT(11) | NOT NULL | Quantidade movimentada. |
| `new_quantity` | INT(11) | NULL | Saldo final após movimentação. |
| `venda_id` | INT(11) | **FK**, UNSIGNED, NULL | Vínculo opcional com venda. |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Data da ação. |

---

## 4. Tabelas Auxiliares e Globais

### `notificacoes`
Alertas do sistema.
| Coluna | Tipo | Atributos | Descrição |
|---|---|---|---|
| `id` | INT | **PK**, AI | Identificador único. |
| `empresa_id` | INT(11) | **FK**, UNSIGNED, NOT NULL | Empresa proprietária. |
| `type` | VARCHAR(50) | NOT NULL | Tipo (ex: 'estoque_baixo'). |
| `message` | TEXT | NOT NULL | Mensagem do alerta. |
| `product_id` | INT(11) | **FK**, UNSIGNED, NULL | Produto relacionado. |
| `is_read` | BOOLEAN | DEFAULT FALSE | Status de leitura. |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Data do alerta. |

### `leads` (Global)
Interessados no produto (Landing Page).
| Coluna | Tipo | Atributos | Descrição |
|---|---|---|---|
| `id` | INT(11) | **PK**, AI, UNSIGNED | Identificador único. |
| `name` | VARCHAR(255) | NOT NULL | Nome do lead. |
| `email` | VARCHAR(255) | UNIQUE, NOT NULL | E-mail de contato. |
| `company_name` | VARCHAR(255) | NULL | Nome da empresa. |
| `challenge` | TEXT | NULL | Descrição do desafio. |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Data de registro. |

### `redefinicoes_senha` (Global)
Tokens temporários.
| Coluna | Tipo | Atributos | Descrição |
|---|---|---|---|
| `email` | VARCHAR(100) | **PK**, NOT NULL | E-mail solicitante. |
| `code` | VARCHAR(6) | NOT NULL | Código de verificação. |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Data da solicitação. |

---

## Legenda
- **PK**: Primary Key (Chave Primária)
- **FK**: Foreign Key (Chave Estrangeira)
- **AI**: Auto Increment
- **UNSIGNED**: Apenas números positivos
