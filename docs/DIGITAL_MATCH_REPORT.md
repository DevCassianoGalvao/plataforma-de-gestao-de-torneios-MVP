# Sumula Digital e PDF

## Fonte e fidelidade

Implementacao usa obrigatoriamente `docs/REFERENCIA_SUMULA.xlsx` e `docs/MATCH_REPORT_MAPPING.md`. A referencia organiza titulo, placar, horario, duas equipes lado a lado, relacao de atletas com numero/AM/VM/gols, assinaturas, arbitragem, mesario, penalti e verso para ocorrencias. O HTML e o PDF preservam esses blocos, sem reduzir o documento a texto corrido.

## Fluxo

Ao homologar partida, `MatchOperationService` chama `MatchReportService`. O service le partida, equipes, escalacoes confirmadas, titulares, reservas, eventos validos, gols, cartoes, substituicoes, horarios, arbitragem, decisoes, penaltis e ocorrencias. Gera preview HTML e PDF A4 com pagina principal e pagina de verso.

Se a mesma fonte for processada novamente, retorna a versao atual sem duplicar. Alteracao autorizada da fonte cria nova versao, novo hash e novo codigo; arquivo e registro anterior permanecem imutaveis.

## Banco e armazenamento

- `match_reports`: um registro por partida, ponte para versao atual e homologacao;
- `match_report_versions`: historico imutavel, numero, hash, codigo de verificacao, HTML e PDF;
- PDFs ficam em `storage/private/reports`, fora de `public`;
- nomes de arquivo sao aleatorios; download passa por autenticacao e escopo.

Migration: `0012_digital_match_reports.sql`.

## Interface e rotas

- `GET /admin/partidas/{id}/sumula`: preview HTML;
- `POST /admin/partidas/{id}/sumula/gerar`: gera versao autorizada com CSRF;
- `GET /admin/partidas/{id}/sumula/pdf`: baixa versao atual;
- `GET /admin/sumulas/versoes/{id}/pdf`: baixa versao historica autorizada;
- `GET /admin/sumulas/rodadas/{id}.zip`: pacote da rodada;
- `GET /admin/sumulas/campeonatos/{id}.zip`: pacote do campeonato.

Administrador e organizador autorizado podem gerar e baixar. Operador e treinador/gestor podem baixar conforme partida autorizada. Comunicacao recebe `403`. Nenhuma rota publica serve sumulas.

## PDF

O writer PHP gera PDF 1.4 real, A4, com fontes PDF padrao WinAnsi, tabela estrutural para cada equipe e duas paginas. A segunda pagina concentra ocorrencias, substituicoes, confirmacoes, organizacao, versao e codigo de verificacao. Gols de disputa de penaltis ficam separados do placar normal.

## Limites do MVP

Retificacao completa e assinatura digital oficial ficam fora desta etapa. O MVP permite gerar nova versao a partir de uma fonte autorizada, sem sobrescrever a anterior. Validacao de caracteres, privacidade, pagina dupla, historico e pacotes esta coberta pelos testes da etapa.
