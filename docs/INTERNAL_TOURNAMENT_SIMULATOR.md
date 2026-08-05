# Simulador interno de partidas e classificação

## Finalidade

O simulador permite criar cenários estratégicos sem alterar registros oficiais. A interface sempre identifica o contexto como `SIMULAÇÃO INTERNA`.

## Estrutura reutilizada

## Simulador publico de resultados

A classificação oficial possui o botão `Simulador`, que abre uma página pública separada em `/campeonatos/{slug}/simulador`. A tabela fica à esquerda e as partidas ficam à direita, com placares vazios, seleção de rodada e avanço ou retorno entre rodadas. O visitante pode preencher qualquer partida publicada da fase de grupos, inclusive uma partida já aprovada, e acompanhar o recálculo em tempo real. O recálculo ocorre por uma rota sem persistência e o resultado não é salvo, publicado, somado a ranking, súmula, prestação de contas ou histórico oficial.

O calculo passa pelo mesmo `StandingsCalculator` da classificacao compartilhada, com a pontuacao e os criterios de desempate do regulamento publicado. O simulador interno salvo continua disponivel somente por rota autorizada direta para cenarios administrativos; seu atalho nao aparece no menu comum.

- fases, grupos, rodadas e equipes existentes apenas como referência;
- regulamento publicado, pontuação e critérios de desempate;
- motor compartilhado `StandingsCalculator`, também usado pela classificação oficial.

## Estrutura nova

Migration `0032_isolated_tournament_simulations.sql` cria:

- `simulation_scenarios`;
- `simulation_matches`;
- `simulation_match_events`.

Essas tabelas não são consultadas por portal público, publicação, súmula, disciplina, artilharia, cartões, suspensões, ranking ou prestação de contas.

## Operação

1. Abra `Simulações` no menu interno.
2. Crie cenário selecionando campeonato e fase.
3. Adicione uma partida existente como referência ou simule uma rodada completa.
4. Informe placares, W.O. hipotético e eventos simulados.
5. Consulte classificação projetada, diferença de pontos e mudança de posição.
6. Duplique, arquive ou exclua logicamente o cenário quando necessário.

Duplicar gera novo cenário e novas linhas próprias. Arquivar mantém histórico. Excluir marca o cenário como removido, sem apagar dados oficiais.

## Permissões

- `simulation.view`
- `simulation.create`
- `simulation.edit`
- `simulation.delete`
- `simulation.compare`
- `simulation.manage`

O administrador recebe todas por padrão. Todas as rotas internas verificam autenticação, permissão e CSRF para alterações.

## Rotas

- `GET /admin/simulacoes`
- `GET /admin/simulacoes/nova`
- `POST /admin/simulacoes`
- `GET /admin/simulacoes/{id}`
- ações protegidas para referência, rodada, placar, evento, duplicação, arquivamento e exclusão.

## Limites intencionais

O cenário não pode ser publicado, convertido automaticamente em partida oficial, incluído em pacote documental ou usado para gerar súmula. O mata-mata oficial também permanece intocado; a projeção indica classificados por grupo, sem criar chave oficial.

## Testes

`SimulationIntegrationTest` valida criação, referência, placar, evento, cálculo, duplicação, arquivamento, exclusão lógica e invariância de tabelas oficiais.

`SimulationHttpTest` valida painel autorizado, CSRF, negação para Prestação de Contas e ausência do cenário no portal público.
