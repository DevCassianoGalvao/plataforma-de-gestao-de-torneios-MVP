# Simulação de Campeonato em Andamento

## Finalidade

Este seed transforma somente o campeonato ficticio `Copa Brasil de Talentos 2026` em uma demonstração navegável de competição em andamento. Ele existe para validação visual e funcional do painel e do portal público.

## Cenário criado

- fase de grupos com 20 partidas homologadas;
- classificação recalculada a partir de gols e assistências reais dos registros de partida;
- quatro classificados em cada Grupo A e Grupo B;
- quartas de final concluidas, incluindo uma decisão por pênaltis;
- duas semifinais com equipes definidas e agendadas para os próximos dias;
- campeonato publicado e com status `in_progress`.

Notícias, Vai e Vem, atletas, inscrições e escalações continuam usando os seeds fictícios já existentes.

## Execução local

Primeiro execute as migrations e o seed base. Depois:

```powershell
$env:ALLOW_DEMO_SIMULATION='1'
C:\xampp\php\php.exe bin\console.php db:seed:simulation
```

## Execução no cPanel

No Terminal do cPanel, dentro da pasta do projeto:

```bash
cd /home/xdigcomb/cassianogalvao.com.br/torneio-online
ALLOW_DEMO_SIMULATION=1 /opt/cpanel/ea-php82/root/usr/bin/php bin/console.php db:seed:simulation
```

Resultado esperado:

```text
SIMULATION_OK fase_de_grupos=concluida quartas=concluidas semifinais=agendadas
```

## Segurança e reexecução

O comando atua apenas no campeonato de demonstração com slug `copa-brasil-de-talentos-2026`. Em `APP_ENV=production`, a variável `ALLOW_DEMO_SIMULATION=1` e obrigatória.

Ele pode ser executado novamente: remove e recria apenas os resultados, eventos e avanços da simulação desse campeonato ficticio, sem criar duplicidades. Nunca execute este comando para representar resultados oficiais ou em um campeonato real.
