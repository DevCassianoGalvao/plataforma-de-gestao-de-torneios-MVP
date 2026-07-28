# Mapeamento da sumula

Fonte analisada integralmente: `docs/REFERENCIA_SUMULA.xlsx`, copiada no staging da rodada. O workbook tem 3 abas: `Súmula` (`A1:AQ40`), `Atletas` (`A1:L306`) e `ArtilhariaCartões` (`A1:G77`). A aba de cadastro contem dados pessoais reais da fonte; nenhum desses registros pode entrar no seed demo.

## Aba `Súmula`

| Faixa | Conteudo | Destino de dominio |
|---|---|---|
| `A1:AQ1` | titulo oficial e identificacao da competicao | `tournaments.name`, temporada e template PDF |
| `A3:A5`, `I3:AF5` | placar final, horario, inicio/termino dos tempos, contagem e desempate por penalti | `matches.home_score`, `matches.away_score`, `match_reports`, `match_shootout_attempts` |
| `A7:M33` | equipe esquerda, atletas, numero, AM, VM e gols 1 a 8 | `teams`, `match_lineups`, `match_events` |
| `W7:AQ33` | equipe direita, mesmos campos | `teams`, `match_lineups`, `match_events` |
| `A36:U36` | assinatura do tecnico e auxiliar/capitao da equipe esquerda | `match_officials`/confirmacoes da sumula |
| `W36:AQ36` | assinatura do tecnico e auxiliar/capitao da equipe direita | `match_officials`/confirmacoes da sumula |
| `A38:I40` | arbitro, arbitro assistente e organizacao | `match_officials` |
| `W38:AQ40` | segundo assistente, mesario e aviso de ocorrencias no verso | `match_officials`, `match_reports.organizer_notes` |

## Aba `Atletas`

Colunas observadas: `EQUIPE`, `FUNCAO`, `NOME COMPLETO`, `POSICAO`, `CPF`, `DATA DE NASCIMENTO`, `WHATSAPP`, `GOLS`, `AMARELO`, `VERMELHO`, `DIA DA SUSPENSAO`, `OCORRENCIA`.

Mapeamento:

- equipe e funcao -> `teams`, `people.person_type`, `team_memberships.role`;
- nome/posicao/nascimento -> `people`, `person_profiles`;
- CPF, WhatsApp, dia de suspensao e ocorrencia -> dados privados, `person_documents`, `suspensions` e `disciplinary_records`;
- gols, amarelo e vermelho -> derivados de `match_events` e `discipline_ledger`, nunca fonte manual do portal.

## Aba `ArtilhariaCartões`

Colunas observadas: `TIME`, `ATLETA`, `GOLS`, `AMARELO`, `VERMELHO`, `DIA DA SUSPENSAO`, `OCORRENCIAS`.

Esta aba e um relatorio consolidado. No sistema deve ser gerada por `player_statistics`, `discipline_ledger` e `suspensions`; nao deve virar uma tabela paralela editavel.

## Regras de geracao

1. Duas equipes aparecem lado a lado.
2. Cada atleta relacionado recebe numero, AM, VM e oito colunas de marcacao de gols para preservar a logica visual da referencia.
3. Placares devem ser validados contra eventos de gol, gol contra e resultado administrativo.
4. Arbitragem, tecnicos, auxiliares/capitaes e mesario sao registros nomeados, nao texto JSON.
5. Ocorrencias vao para a segunda pagina/verso; PDF registra versao, data e homologador.
6. Elencos maiores que a capacidade da primeira pagina geram pagina de continuacao, sem truncar atletas.
7. Geracao usa dados homologados e cria snapshot imutavel em `match_homologation_versions`.

## Estado desta rodada

Existe `MatchReportService`, visualizacao HTML e `PdfReportService` estrutural. O PDF atual ainda nao replica todo o grid da planilha; acabamento fiel, assinaturas digitais e layout final ficam pendentes da rodada de UI/UX/PDF.
