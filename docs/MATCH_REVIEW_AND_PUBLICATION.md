# Revisao, retificacao e publicacao de partidas

## Estados independentes

Uma partida tem tres decisoes separadas:

1. **Operacao**: em operacao, aguardando revisao ou aprovada.
2. **Revisao**: em preenchimento, aguardando revisao, devolvida, rejeitada ou aprovada.
3. **Publicacao**: interna, agendada, publicada ou retirada do portal.

O portal somente recebe dados de partidas aprovadas que tambem estejam publicadas. Isso inclui agenda, resultados, detalhe da partida, historico de equipes, perfil do atleta e rankings de gols e assistencias.

## Fluxo oficial

1. O operador preenche escalações, arbitragem, horarios, eventos e substituicoes.
2. O operador finaliza e envia a partida para revisao.
3. O administrador aprova, devolve para correcao ou rejeita o envio com motivo.
4. A aprovacao recalcula os dados esportivos e gera a versao da sumula.
5. A publicacao no portal e uma decisao separada do administrador.

## Retificacao

Depois de aprovada, a partida nao pode ser alterada silenciosamente. Um pedido de retificacao exige motivo. Quando aprovado:

- a partida volta para correcao;
- a publicacao e retirada do portal;
- o motivo e a decisao ficam no historico;
- as versoes anteriores da sumula continuam preservadas;
- uma nova aprovacao gera uma nova versao documental.

## Anulacao de evento

Antes da aprovacao, um administrador pode anular um evento com justificativa. O registro continua guardado com o estado `Anulado`; ele nao e apagado.

## Operacao do cron

Para publicar agendamentos vencidos no cPanel:

```text
* * * * * cd /home/SEU_USUARIO/cassianogalvao.com.br/torneio-online && /opt/cpanel/ea-php82/root/usr/bin/php bin/console.php matches:publish-due >> /home/SEU_USUARIO/torneios-publicacoes.log 2>&1
```

O comando e idempotente: rodar novamente nao duplica publicacoes.
