# Súmula Digital e PDF

## Fonte e fidelidade

Implementação usa obrigatoriamente `docs/REFERENCIA_SUMULA.xlsx` e `docs/MATCH_REPORT_MAPPING.md`. A referência organiza título, placar, horário, duas equipes lado a lado, relação de atletas com número/AM/VM/gols, assinaturas, arbitragem, mesário, penalti e verso para ocorrências. O HTML e o PDF preservam esses blocos, sem reduzir o documento a texto corrido.

## Fluxo

Ao homologar partida, `MatchOperationService` chama `MatchReportService`. O service le partida, equipes, escalações confirmadas, titulares, reservas, eventos válidos, gols, cartões, substituições, horários, arbitragem, decisões, pênaltis e ocorrências. Gera preview HTML e PDF A4 com página principal e página de verso.

Se a mesma fonte for processada novamente, retorna a versão atual sem duplicar. Alteração autorizada da fonte cria nova versão, novo hash e novo código; arquivo e registro anterior permanecem imutáveis.

## Banco e armazenamento

- `match_reports`: um registro por partida, ponte para versão atual e homologação;
- `match_report_versions`: histórico imutável, número, hash, código de verificação, HTML e PDF;
- PDFs ficam em `storage/private/reports`, fora de `public`;
- nomes de arquivo são aleatórios; download passa por autenticação e escopo.

Migration: `0012_digital_match_reports.sql`.

## Interface e rotas

- `GET /admin/partidas/{id}/sumula`: preview HTML;
- `POST /admin/partidas/{id}/sumula/gerar`: gera versão autorizada com CSRF;
- `GET /admin/partidas/{id}/sumula/pdf`: baixa versão atual;
- `GET /admin/sumulas/versoes/{id}/pdf`: baixa versão historica autorizada;
- `GET /admin/sumulas/rodadas/{id}.zip`: pacote da rodada;
- `GET /admin/sumulas/campeonatos/{id}.zip`: pacote do campeonato.

Administrador e organizador autorizado podem gerar e baixar. Operador e treinador/gestor podem baixar conforme partida autorizada. Comunicação recebe `403`. Nenhuma rota pública serve súmulas.

## PDF

O writer PHP gera PDF 1.4 real, A4, com fontes PDF padrão WinAnsi, tabela estrutural para cada equipe e duas páginas. A segunda página concentra ocorrências, substituições, confirmações, organização, versão e código de verificação. Gols de disputa de pênaltis ficam separados do placar normal.

## Limites do MVP

Retificação completa e assinatura digital oficial ficam fora desta etapa. O MVP permite gerar nova versão a partir de uma fonte autorizada, sem sobrescrever a anterior. Validação de caracteres, privacidade, página dupla, histórico e pacotes está coberta pelos testes da etapa.
