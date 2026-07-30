# Simulacao de Campeonato em Andamento

## Finalidade

Este seed transforma somente o campeonato ficticio `Copa Brasil de Talentos 2026` em uma demonstracao navegavel de competicao em andamento. Ele existe para validacao visual e funcional do painel e do portal publico.

## Cenario criado

- fase de grupos com 20 partidas homologadas;
- classificacao recalculada a partir de gols e assistencias reais dos registros de partida;
- quatro classificados em cada Grupo A e Grupo B;
- quartas de final concluidas, incluindo uma decisao por penaltis;
- duas semifinais com equipes definidas e agendadas para os proximos dias;
- campeonato publicado e com status `in_progress`.

Noticias, Vai e Vem, atletas, inscricoes e escalacoes continuam usando os seeds ficticios ja existentes.

## Execucao local

Primeiro execute as migrations e o seed base. Depois:

```powershell
$env:ALLOW_DEMO_SIMULATION='1'
C:\xampp\php\php.exe bin\console.php db:seed:simulation
```

## Execucao no cPanel

No Terminal do cPanel, dentro da pasta do projeto:

```bash
cd /home/xdigcomb/cassianogalvao.com.br/torneio-online
ALLOW_DEMO_SIMULATION=1 /opt/cpanel/ea-php82/root/usr/bin/php bin/console.php db:seed:simulation
```

Resultado esperado:

```text
SIMULATION_OK fase_de_grupos=concluida quartas=concluidas semifinais=agendadas
```

## Seguranca e reexecucao

O comando atua apenas no campeonato de demonstracao com slug `copa-brasil-de-talentos-2026`. Em `APP_ENV=production`, a variavel `ALLOW_DEMO_SIMULATION=1` e obrigatoria.

Ele pode ser executado novamente: remove e recria apenas os resultados, eventos e avancos da simulacao desse campeonato ficticio, sem criar duplicidades. Nunca execute este comando para representar resultados oficiais ou em um campeonato real.
