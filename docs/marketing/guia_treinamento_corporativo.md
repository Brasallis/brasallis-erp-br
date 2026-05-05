# Manual de Treinamento Corporativo: Sistema de Gestão de Ativos e Estoque

Este documento foi desenvolvido para orientar colaboradores e gestores na utilização da versão Enterprise do nosso sistema. O foco é garantir a integridade dos dados, a agilidade operacional e a conformidade com as normas da empresa.

---

## 1. Introdução à Cultura de Dados
A eficiência da nossa logística depende da precisão dos registros. Cada entrada e saída de material deve ser documentada no momento exato da operação para garantir que o Dashboard Gerencial reflita a realidade do nosso fluxo de caixa e capital de giro.

---

## 2. Acesso e Segurança de Perfil
O sistema utiliza controle de acesso baseado em funções (RBAC).
- **Colaboradores Operacionais:** Focados em lançamentos de movimentação e consulta de estoque.
- **Gestores/Administradores:** Acesso a relatórios fiscais, ajustes de inventário e auditoria.

**Passos Iniciais:**
1. Acesse o portal corporativo com suas credenciais únicas.
2. Verifique se seu perfil está atualizado em `editar_perfil.php`.
3. Mantenha sua senha segura; o sistema registra todas as ações por ID de usuário para fins de auditoria interna.

---

## 3. Gestão de Inventário (O Coração da Operação)
### Cadastro de Produtos
- Utilize a padronização de nomes adotada pela empresa.
- Certifique-se de que o **SKU/Código Interno** esteja correto antes de salvar.
- Campos obrigatórios: Categoria, Preço de Custo, Preço de Venda e Estoque Mínimo.

### Registro de Movimentações
- **Entradas:** Devem ser conferidas com a nota fiscal em mãos.
- **Saídas:** Devem estar atreladas a uma ordem de serviço ou pedido de venda.
- **Ajustes:** Reservados para quebras ou perdas justificadas (requer aprovação de nível superior).

---

## 4. Dashboards e Tomada de Decisão
Os gráficos de monitoramento em tempo real mostram:
- **Ruptura de Estoque:** Produtos abaixo do nível de segurança.
- **Curva ABC:** Quais ativos geram mais valor e quais estão "parados".
- **Logs de Atividade:** Histórico completo de quem alterou o quê e quando.

---

## 5. Melhores Práticas e Compliance
- **Zero Planilhas Paralelas:** O sistema é a nossa única fonte da verdade. O uso de Excel para controle de estoque está sendo descontinuado para evitar divergências.
- **Sincronização:** O sistema é baseado em nuvem (ou servidor interno dedicado), garantindo que a equipe de compras veja a necessidade de reposição no instante em que o vendedor faz a baixa.

---

## 6. Suporte e Resolução de Problemas
Caso encontre inconsistências no esquema de dados ou erros de renderização:
1. Verifique se sua permissão permite a ação desejada.
2. Contate o suporte de TI interno informando o código do erro ou a URL da página (ex: `v2_fiscal_migration.php`).

---

> [!IMPORTANT]
> A precisão do sistema é o que garante a nossa margem de lucro e a disponibilidade de produtos para nossos clientes. Use-o com responsabilidade e atenção.
