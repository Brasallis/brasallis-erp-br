# Documento de Definição de Precificação - Brasallis

**Versão:** 1.0
**Público:** Interno (Estratégia de Negócio)

---

## 1. Objetivo

Este documento serve como um guia para definir a estratégia de precificação do **Brasallis**. O objetivo é estabelecer um modelo de preços que seja competitivo, que capture o valor gerado para o cliente, que seja fácil de entender e que sustente o crescimento do negócio a longo prazo.

## 2. Análise de Valor

Antes de definir o preço, é crucial entender o **valor** que o sistema entrega. Nossos principais diferenciais de valor são:

1.  **Economia de Tempo (Valor Quantificável):**
    *   **Principal Ativo:** A extração de dados de notas fiscais via IA. Podemos estimar o tempo que um funcionário leva para digitar manualmente uma nota e comparar com o tempo de upload e validação no sistema.
    *   *Exemplo de Cálculo:* Se um funcionário leva 5 minutos por nota e processa 100 notas/mês, são 500 minutos (mais de 8 horas) de trabalho. O **Brasallis** reduz isso para menos de 1 hora.

2.  **Redução de Custos e Prejuízos (Valor Quantificável):**
    *   **Principal Ativo:** Notificações de estoque baixo e produtos perto do vencimento.
    *   Isso evita a **ruptura de estoque** (perder uma venda por falta de produto) e a **perda de mercadoria** por validade expirada.

3.  **Tomada de Decisão Estratégica (Valor Qualitativo):**
    *   **Principal Ativo:** Dashboards e relatórios inteligentes.
    *   Fornecer clareza sobre quais produtos vendem mais, margens de lucro e tendências de vendas permite que o dono do negócio tome decisões mais inteligentes sobre compras e estratégias.

4.  **Segurança e Organização (Valor Qualitativo):**
    *   **Principal Ativo:** Arquitetura Multi-tenant e controle de acesso robusto.
    *   A tranquilidade de ter os dados seguros, organizados e acessíveis de qualquer lugar.

## 3. Modelos de Precificação Considerados

Com base na análise do sistema e do mercado de SaaS, o modelo mais adequado é o de **Assinatura Mensal (Subscription)**.

### 3.1. Justificativa do Modelo de Assinatura

*   **Receita Previsível:** Gera um fluxo de caixa constante.
*   **Barreira de Entrada Baixa:** O cliente não precisa de um grande investimento inicial.
*   **Alinhamento com o Cliente:** O sucesso do nosso negócio depende do sucesso contínuo do cliente com a plataforma.
*   **Infraestrutura:** O modelo de assinatura cobre os custos contínuos de servidor, manutenção e, principalmente, o **custo da API de IA**.

## 4. Estratégia de Tiers (Planos)

Propor diferentes planos (tiers) permite atender a diferentes perfis de clientes e criar um caminho de crescimento (upsell).

**Fatores para Diferenciar os Planos:**

*   **Número de Usuários:** Quantos funcionários podem acessar o sistema.
*   **Volume de Processamento de IA:** Limite de notas fiscais processadas por mês.
*   **Funcionalidades Avançadas:** Relatórios mais detalhados, histórico de dados mais longo, etc.

### **Proposta de Planos:**

#### **Plano Básico (Empreendedor Individual)**

*   **Público-alvo:** Microempresas ou autônomos que estão começando a organizar seu estoque.
*   **Recursos:**
    *   **1 Usuário** (o próprio admin).
    *   Até **20 processamentos de IA** por mês.
    *   Funcionalidades essenciais: CRUD de produtos, fornecedores, dashboards básicos.
    *   Suporte via e-mail.
*   **Preço Sugerido:** R$ 39,90/mês.

#### **Plano Profissional (Pequenas Empresas) - *O mais popular***

*   **Público-alvo:** Pequenas lojas, varejistas e distribuidores com uma pequena equipe.
*   **Recursos:**
    *   Até **5 Usuários** (1 admin + 4 funcionários).
    *   Até **100 processamentos de IA** por mês.
    *   Todas as funcionalidades do Básico.
    *   **Relatórios Avançados** e filtros por período.
    *   **Notificações** de estoque e validade.
    *   Suporte via chat e e-mail.
*   **Preço Sugerido:** R$ 89,90/mês.

#### **Plano Empresarial (Negócios em Crescimento)**

*   **Público-alvo:** Empresas com maior volume de operações e necessidade de mais automação.
*   **Recursos:**
    *   **Usuários Ilimitados**.
    *   **Processamentos de IA Ilimitados** (ou um limite bem alto, ex: 500).
    *   Todas as funcionalidades do Profissional.
    *   **Histórico de dados estendido** (ex: 2 anos vs 1 ano).
    *   Suporte prioritário por telefone.
    *   (Futuro) Integrações com outras plataformas (e-commerce, etc.).
*   **Preço Sugerido:** R$ 199,90/mês.

---

## 5. Estratégias Adicionais

### 5.1. Teste Gratuito (Free Trial)

*   Oferecer um **trial de 14 dias** do **Plano Profissional** é uma excelente forma de o cliente experimentar o valor máximo da ferramenta sem compromisso.
*   O sistema já possui a estrutura para isso (`trial_ends_at` no banco de dados).

### 5.2. Desconto Anual

*   Oferecer um desconto para pagamento anual (ex: "Pague 10 meses e leve 12") pode melhorar o fluxo de caixa e a retenção de clientes.

### 5.3. Custo da IA

*   **Ponto Crítico:** A precificação **DEVE** levar em conta o custo da API de IA. O limite de processamentos nos planos Básico e Profissional serve como uma salvaguarda para controlar esse custo.

## 6. Próximos Passos

1.  **Validar o Custo da IA:** Levantar o custo exato por chamada da API de IA para refinar os limites e os preços dos planos.
2.  **Análise de Concorrência:** Pesquisar o preço de sistemas de gestão de estoque concorrentes.
3.  **Implementar a Lógica de Planos:** Desenvolver no sistema a lógica que restringe as funcionalidades com base no plano.
4.  **Criar uma Página de Preços:** Desenvolver a página `precos.php` no site **Brasallis**.
