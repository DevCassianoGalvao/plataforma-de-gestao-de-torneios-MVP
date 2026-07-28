# Arquitetura

Aplicação monolítica modular PHP 8.2, sem framework de execução. `public/index.php` é o front controller; rotas são resolvidas por `App\Support\Router`.

`Controllers` coordenam HTTP. `Repositories` concentram SQL PDO. `Services` concentram regras. `Policies` verificam autenticação e escopo. `Views` são templates PHP sem regra de negócio.

Identidade visual é armazenada em `tournament_themes` e aplicada como custom properties CSS. Conteúdo público consulta apenas registros ativos e campos permitidos. Dados privados de `people` permanecem administrativos.

O isolamento inicial usa usuário, organização, projeto, campeonato e equipe. Superadministrador possui escopo global; demais perfis deverão receber atribuições explícitas em `user_role_assignments`.

Operações críticas usam transação e `audit_logs`. Exclusão é lógica nas tabelas de negócio. Regras esportivas ficam em JSON em `tournament_settings`, permitindo outros formatos sem alterar código.
