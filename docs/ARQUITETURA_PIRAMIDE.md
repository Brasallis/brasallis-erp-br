# Arquitetura Organizacional (Pirâmide) - Guia de Uso

Este documento descreve as novas funcionalidades implementadas para transformar o sistema em um ERP com hierarquia organizacional completa.

## 1. Visão Geral
Agora o sistema suporta uma estrutura hierárquica onde:
1.  **Empresa** tem **Setores** (Departamentos).
2.  **Setores** têm **Permissões** (o que podem acessar) e **Cargos** (hierarquia de pessoas).
3.  **Usuários** são atribuídos a um **Setor** e um **Cargo**.

## 2. Novas Funcionalidades

### A. Gestão de Organização
Acesse **Corporativo > Organização** no menu lateral.
*   **Visualização Pirâmide**: Veja graficamente a estrutura da sua empresa.
*   **Gestão de Setores**: Crie departamentos como Financeiro, RH, Estoque, Vendas.

### B. Configuração de Setor (RBAC)
Cada setor tem uma página de configuração detalhada (clique em "Configurar Módulos" ou "Gerenciar").
*   **Permissões de Acesso**: Defina quais módulos cada setor pode acessar e em qual nível:
    *   👁️ **Leitura**: Apenas visualiza dados.
    *   ✏️ **Leitura e Escrita**: Pode criar/editar.
    *   🛠️ **Controle Total**: Acesso administrativo ao módulo.
*   **Cargos**: Crie a escada corporativa do setor (ex: Estagiário -> Analista -> Gerente). Defina níveis de 1 a 10.

### C. Gestão de Usuários e Atribuição
Acesse **Corporativo > Acessos**.
*   Ao criar ou editar um usuário, agora você pode selecionar o **Setor** e o **Cargo**.
*   O sistema vincula o usuário automaticamente às permissões daquele setor.

## 3. Segurança e Controle Interno
O sistema de login foi atualizado para carregar automaticamente as permissões do usuário na sessão.
Novas funções de segurança garantem o controle granular:
*   `check_permission('estoque', 'escrita')`: Verifica se o usuário pode alterar o estoque.
*   `has_permission('financeiro')`: Verifica se o usuário pode ver o financeiro.

## 4. Próximos Passos Recomendados
1.  **Migrar Usuários**: Edite os usuários existentes e atribua-os aos setores corretos.
2.  **Refinar Menus**: O menu lateral pode ser atualizado para ocultar itens que o usuário não tem permissão (atualmente ele mostra baseado em se é 'admin' ou 'funcionario').
3.  **Proteger Rotas PHP**: Adicionar verificação `check_permission` no início de arquivos críticos (ex: `cria_produto.php`).
