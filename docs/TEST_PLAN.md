# Plano de testes

## Classificacao

Nao usar `E2E` para teste que apenas leia arquivos. Teste de interface precisa atravessar HTTP/browser; teste de service precisa persistir e verificar resultado.

## Camadas e cenarios

| Camada | Cenarios minimos | Evidencia |
|---|---|---|
| Unitario | slug, validacao de regra, distribuicao automatica por posicao, desempate e vencedor por penalti | testes pequenos sem HTTP |
| Integracao | migrations, FK, seed idempotente, repositorio, escopo e auditoria | banco descartavel |
| Service | inscricao, agenda idempotente, escalação, eventos, homologacao, suspensao, classificacao, chave e PDF | `tests/tournament_e2e.php`, `tests/sports_rules_e2e.php`, `tests/rectification_e2e.php` |
| HTTP | login, CSRF, redirecionamento por perfil, isolamento por slug, rotas publicas e downloads | `tests/*_http_e2e.php` com servidor local |
| Interface | shell, CSS, textos tecnicos ausentes, estados e paginas publicas | `tests/*_ui_e2e.php`; browser real ainda recomendado |

## Suite da rodada

- `php -l` em todos os PHP.
- migrations em banco descartavel.
- `php database/seed.php --demo` em banco vazio.
- seed repetido sem duplicar entidades demo.
- `integration.php`, `authorization_e2e.php`, `tournament_e2e.php`, `sports_rules_e2e.php`, `rectification_e2e.php`.
- servidor PHP local para `management_http_e2e.php`, `navigation_http_e2e.php`, `ui_structure_http_e2e.php`.
- portal publico com campeonato ativo, campeonato draft e slug inexistente.

## Criterios de aprovacao

- nenhum erro de sintaxe;
- todas as migrations aplicam em ordem;
- seed e reexecucao sao idempotentes;
- organizador nao ve outro campeonato;
- operador so opera partida atribuida;
- atleta suspenso nao confirma escalação;
- agenda repetida nao duplica confronto;
- homologacao atualiza tabela, disciplina, chave e versao da sumula;
- portal nao expõe dados pessoais ou documentos privados;
- falhas de infraestrutura ficam explicitamente marcadas, nunca como PASS.

## Lacunas conhecidas

- ambiente desta maquina nao tem Playwright configurado no projeto;
- PDF ainda estrutural, nao uma replica visual pixel a pixel da planilha;
- distribuicao automatica e campo tatico visual definitivo ainda precisam de service/teste dedicado;
- noticias e Vai e Vem tem schema e presenter, mas fluxo administrativo completo requer teste HTTP especifico.
