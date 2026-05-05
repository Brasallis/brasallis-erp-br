# Documentação da API de Integração - V1

## Visão Geral
Esta API permite que sistemas externos interajam com o **Gerenciador de Estoque & CRM**. A comunicação é realizada via HTTPS, retornando dados em formato **JSON**.

**Base URL:**
`http://seu-dominio.com/gerenciador_de_estoque/api/v1`

---

## Autenticação
Toda requisição deve incluir um cabeçalho `Authorization` com uma chave de API válida (Bearer Token).

```http
Authorization: Bearer sk_Production_123456...
```

> ** Nota:** As chaves de API são gerenciadas pelo administrador do sistema na tabela `api_keys`.

---

## Recursos Disponíveis

### 1. CRM: Clientes
Gerencie sua base de clientes.

#### Listar Clientes
Retorna uma lista paginada de clientes.

**Endpoint:** `GET /crm/clientes.php`

**Parâmetros (Query Params):**
| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `page` | int | 1 | Número da página requisitada. |
| `limit` | int | 50 | Quantidade de registros por página (Max: 100). |
| `search` | string | null | Filtra por nome, e-mail ou CPF/CNPJ. |

**Exemplo de Requisição:**
```bash
curl -X GET "http://localhost/api/v1/crm/clientes.php?page=1&limit=10" \
  -H "Authorization: Bearer sk_test_..."
```

**Exemplo de Resposta (200 OK):**
```json
{
  "data": [
    {
      "id": 105,
      "nome": "Empresa Exemplo LTDA",
      "tipo": "PJ",
      "email": "contato@exemplo.com",
      "cpf_cnpj": "12.345.678/0001-90",
      "cidade": "São Paulo",
      "estado": "SP"
    }
  ],
  "meta": {
    "total": 150,
    "page": 1,
    "limit": 10,
    "total_pages": 15
  }
}
```

#### Criar Novo Cliente
Cadastra um novo cliente no CRM.

**Endpoint:** `POST /crm/clientes.php`

**Corpo da Requisição (JSON):**
| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `nome` | string | **Sim** | Nome completo ou Razão Social. |
| `tipo` | string | Não | `PF` ou `PJ`. Padrão: `PF`. |
| `email` | string | Não | E-mail de contato. |
| `cpf_cnpj` | string | Não | Apenas números. |
| `telefone` | string | Não | Telefone principal. |

**Exemplo de Requisição:**
```bash
curl -X POST "http://localhost/api/v1/crm/clientes.php" \
  -H "Authorization: Bearer sk_test_..." \
  -H "Content-Type: application/json" \
  -d '{
    "nome": "João da Silva",
    "email": "joao@email.com",
    "tipo": "PF"
  }'
```

**Exemplo de Resposta (201 Created):**
```json
{
  "message": "Client created",
  "id": 106
}
```

---

## Códigos de Erro
A API utiliza códigos HTTP padrão para indicar sucesso ou falha.

| Código | Descrição |
|--------|-----------|
| `200` | Sucesso. |
| `201` | Criado com sucesso. |
| `400` | Requisição inválida (Faltam campos obrigatórios). |
| `401` | Não autorizado (Token ausente ou inválido). |
| `403` | Proibido (Token válido, mas sem permissão para este recurso). |
| `405` | Método não permitido (Ex: Tentar DELETE em endpoint apenas GET). |
| `500` | Erro interno do servidor. |
