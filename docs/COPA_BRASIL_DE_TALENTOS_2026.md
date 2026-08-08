# Copa Brasil de Talentos 2026

## Objetivo

Este documento registra a configuração inicial da Copa Brasil de Talentos 2026 no Torneio Online Web App. A fonte utilizada foi o PDF enviado na pasta `COPA BRASIL DE TALENTOS 2026`.

## Configuração aplicada

- Temporada: 2026.
- Categoria: Adulto Masculino.
- Dez equipes, distribuídas em dois grupos de cinco.
- Fase de grupos em turno único, com as equipes jogando entre si.
- Dois classificados por grupo.
- Semifinais: primeiro do Grupo A contra segundo do Grupo B e primeiro do Grupo B contra segundo do Grupo A.
- Semifinais e final em ida e volta.
- Pontuação: vitória vale 3 pontos, empate vale 1 ponto e derrota vale 0 ponto.
- Desempates: confronto direto, vitórias, saldo de gols, menor número de gols sofridos, menor número de cartões somados e sorteio.
- Partida: 90 minutos, em dois tempos de 45, com intervalo de 15 minutos.
- Até sete substituições por equipe.
- Elenco configurado com 22 atletas e até três integrantes da comissão técnica.
- Até cinco substituições de integrantes durante a competição, com solicitação até cinco dias úteis antes da partida.
- Até oito atletas de outros municípios quando necessário para completar o limite regulamentar.
- Atleta precisa ter participado de pelo menos uma partida da primeira fase para atuar na segunda fase.
- Número de camisa fixo, salvo autorização da organização.
- Três cartões amarelos ou um cartão vermelho geram suspensão automática na partida seguinte.
- Cartões amarelos são zerados para classificados ao fim da primeira fase, sem apagar suspensões pendentes.
- W.O. exige pelo menos sete atletas aptos e identificados, respeitada a tolerância de 15 minutos.
- W.O. usa o placar administrativo de 3 a 0, não entra no saldo para desempate nem na artilharia e elimina a equipe conforme o regulamento.

## Equipes e arquivos

O seed importa os dez escudos fornecidos e vincula a identidade visual ao respectivo clube. O PDF do regulamento também é armazenado como documento do regulamento para consulta autorizada.

Os nomes utilizados na ordem dos arquivos enviados são:

1. Boa Esperança FC
2. Mury FC
3. Viguinha FC
4. Sana FC
5. Lumiar FC
6. Santiago FC
7. Retiro Saudoso FC
8. Bragantino FC
9. Ousadia e Alegria FC
10. Rio Bonito FC

A distribuição inicial é sequencial: cinco primeiras equipes no Grupo A e cinco seguintes no Grupo B. O regulamento não informa datas, horários, locais ou nomes completos de todos os treinadores; por isso esses dados não foram inventados e permanecem editáveis no painel.

## Execução

Depois de enviar o código e a pasta de arquivos para o servidor, execute na raiz do projeto:

```bash
php bin/console.php migrate
php bin/console.php db:seed:copa-brasil-2026
```

O comando é idempotente. Executá-lo novamente atualiza a configuração e reutiliza o campeonato, o regulamento, as equipes e os grupos existentes, sem duplicar registros.

Em uma instalação nova, defina `SEED_DEMO_PASSWORD` antes da primeira execução para que o seed base crie o administrador inicial. Em ambiente de produção, o seed específico é bloqueado por segurança; faça a configuração em ambiente controlado ou remova temporariamente essa proteção somente seguindo o procedimento operacional aprovado.

## O que ainda depende de operação

O seed não cria partidas com datas fictícias, atletas, inscrições ou resultados. Esses dados devem ser cadastrados pelo fluxo administrativo quando a organização fornecer as informações reais. A estrutura de W.O., os campos regulamentares e a configuração do mata-mata estão preparados, mas a decisão de W.O. deve ser registrada pelo operador e aprovada conforme o fluxo oficial.

O modelo atual já suporta a configuração de ida e volta. O cálculo agregado de uma disputa de duas partidas e a geração automática dos dois jogos de cada confronto devem ser validados em uma rodada específica antes da publicação da tabela oficial.
