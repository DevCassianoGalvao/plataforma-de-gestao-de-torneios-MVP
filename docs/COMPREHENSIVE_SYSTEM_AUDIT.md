# Auditoria Tecnica Consolidada

Data da revisao: 2026-08-04.

## Decisoes aplicadas nesta rodada

O sistema ja possuia operacao esportiva, aprovacao de resultado, calculo de classificacao, sumula versionada, evidencias privadas, portal publico, auditoria e backup local. O risco principal era que uma partida aprovada passava a aparecer no portal sem uma decisao editorial independente.

Foi criada a tabela `match_publications` e a permissao exclusiva de administrador para controlar a visibilidade de cada partida. Uma partida pode ficar interna, publicada, agendada ou retirada do portal. Agenda, resultado e detalhe publico agora exigem estado `published`. A publicacao agendada e processada por comando idempotente. O seed de demonstracao marca apenas seus proprios dados como publicados; partidas reais novas continuam internas ate decisao expressa.

A migration `0029_match_review_rectification.sql` adiciona revisao formal, devolucao com motivo, rejeicao, pedidos de retificacao e anulacao justificada de eventos. Uma retificacao aprovada devolve a partida para correcao, retira sua publicacao e preserva as versoes anteriores de sumula.

## Confirmado na base atual

- Fluxo de operacao da partida com escalacoes, eventos, substituicoes, arbitragem, encerramento e aprovacao.
- Recalculo esportivo derivado de partidas aprovadas.
- Sumulas com versoes imutaveis e armazenamento privado.
- Evidencias de partida privadas, com validacao de arquivo e conversao de imagem para WebP.
- Perfis separados para administrador, gestor de equipe, operador de partida e prestacao de contas.
- Portal com isolamento de dados privados, identidade do campeonato, noticias, transferencias, tabela e paginas de equipes e atletas.
- Auditoria, notificacoes administrativas, backups locais e restauracao verificada.

## Pendencias que nao podem ser declaradas como concluidas

- Segunda aprovacao configuravel por tipo de evento e edicao pontual de evento ja aprovado sem retorno completo para correcao.
- Painel consolidado de cobertura por rodada ainda é melhoria futura; o checklist configurável de evidências por campeonato, revisão, bloqueios e exceções foi entregue na migration `0030`.
- Publicacao independente para classificacao, rankings, chave e demais agregados. Nesta rodada a classificacao e ocultada quando existir resultado aprovado ainda nao publicado no grupo, evitando divulgar dado incompleto.
- Integracao real com provedor externo de backup. A base atual possui backup local e documentacao de copia externa, nao uma integracao de armazenamento remoto.
- Cobertura completa de exclusao logica para todas as entidades historicas e politica formal de retencao por tipo de dado.
- Teste de restauracao no ambiente cPanel de producao, que depende de janela controlada e de um backup real.

## Regra operacional recomendada

1. Operador registra a partida e envia evidencias.
2. Administrador confere e aprova o resultado.
3. Administrador revisa a sumula e decide a publicacao da partida no portal.
4. O cron processa publicacoes futuras sem duplicar registros.
5. Qualquer retirada do portal exige justificativa e fica auditada.

## Validacao executada

Em banco descartavel, a suite integrada foi executada apos a migration `0029_match_review_rectification.sql`:

```text
MVP_TESTS_OK unit=17 integration=16 http=16
```

## Cron de publicacao no cPanel

```text
* * * * * cd /home/xdigcomb/cassianogalvao.com.br/torneio-online && /opt/cpanel/ea-php82/root/usr/bin/php bin/console.php matches:publish-due >> /home/xdigcomb/torneios-publicacoes.log 2>&1
```

O caminho do PHP deve ser ajustado ao exibido pelo MultiPHP do cPanel.
