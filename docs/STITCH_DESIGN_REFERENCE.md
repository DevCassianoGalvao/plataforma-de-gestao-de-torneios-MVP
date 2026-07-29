# Referencia visual do Stitch

## Consulta

- Projeto: `Football Management Login Interface`
- Identificador consultado via MCP: `projects/11240560855354740609`
- Data da consulta: 2026-07-29
- Origem: projeto privado acessivel pelo servidor MCP do Google Stitch

Nenhuma chave, token, credencial ou dado interno de autenticacao foi registrado neste documento.

## Telas encontradas

- `Login - Desktop`
- `Login - Tablet`
- `Login - Mobile`
- `Login - Component States`
- `Home - Portal do Campeonato`
- `Portal Home - Desktop`
- `Jogos - Portal do Campeonato`
- `Jogos - Refinado (PT-BR)`
- `Detalhe da Partida - Portal do Campeonato`
- `Detalhe da Partida - Refinado (PT-BR)`
- `Classificacao - Portal do Campeonato`
- `Classificacao - Refinado (PT-BR)`
- `Standings - Desktop`
- `Match Results - Desktop`
- `Match Detail & Tactical Field - Desktop`

As telas esportivas sao referencias complementares. O projeto se concentra na linguagem visual e possui maior detalhe no login.

## Elementos extraidos

### Paleta

- Fundo principal noturno: `#0A1422`
- Nivel mais profundo: `#050E1C`
- Superficie: `#101C2B` e `#16202F`
- Superficie elevada: `#212A39` e `#2C3545`
- Texto principal: `#D9E3F7`
- Texto secundario: `#C3C6D7`
- Primaria de acao: `#2563EB`
- Primaria clara: `#B4C5FF`
- Verde de confirmacao: `#22C55E` / `#4AE176`
- Azul de apoio: `#60A5FA`
- Erro: `#FFB4AB`

### Tipografia

- Hanken Grotesk para titulos, placares, numeros e labels taticos.
- Inter para textos, formularios, tabelas e navegacao.
- Display de referencia: 48/56px, peso 800.
- Headline principal: 32/40px, peso 700.
- Headline compacto: 24/32px, peso 600.
- Corpo: 16/24px.
- Labels: 12-14px, peso 500-600, com leve espaco entre letras.

### Espacamento, grid e forma

- Ritmo base de 8px.
- Escala recorrente: 4, 8, 12, 16, 24 e 40px.
- Desktop com grid de 12 colunas e gutter de 24px.
- Tablet com 8 colunas e gutter de 20px.
- Mobile com 4 colunas e margens de 16px.
- Conteudo administrativo limitado a 1280px.
- Raios pequenos de 2px, padrao de 4px, controles maiores de 8px e destaque de 12px.
- Profundidade por camadas tonais e contornos brancos sutis, sem sombras pesadas.

### Componentes e estados

- Login com painel visual forte, formulario escuro, campos em baixo relevo e foco azul.
- Botoes primarios azuis com texto claro; secundarios em ghost com contorno discreto.
- Cards com camadas de superficie, borda de baixa opacidade e hover com elevacao minima.
- Listas e tabelas com linhas discretas e destaque de linha no hover.
- Badges de status com cor de apoio e texto explicativo, sem depender apenas de cor.
- Campo tatico como superficie funcional, com coordenadas e controles visiveis.
- Estados de erro, foco, loading e sucesso previstos no conjunto de componentes do login.

## Aplicacao no produto real

- O painel administrativo adota a atmosfera de centro de comando, com sidebar, header contextual, camadas tonais e acentos de campo.
- O login recebe a maior fidelidade de composicao, mantendo os fluxos atuais de CSRF, sessao e recuperacao de senha.
- O portal publico usa a mesma familia tipografica e acentos esportivos, mas possui hierarquia, navegacao e superficie proprias para leitura publica.
- As cores cadastradas por campeonato continuam soberanas no portal; o sistema limita contraste e aplica fallback seguro.
- O campo tatico, a central da partida e a sumula recebem acabamento de produto, sem transformar operacao ou documento em decoracao.

## O que nao deve ser copiado

- Nenhum logotipo, texto, nome, imagem proprietaria ou composicao identica do Stitch.
- Nenhum layout de login deve ser duplicado em tabelas, formularios ou paginas publicas.
- Nenhum dado ficticio da referencia substitui dados reais do campeonato.
- Gradientes, texturas e acentos so entram quando servem a hierarquia e nao reduzem contraste ou leitura.

## Limitacoes da referencia

- O Stitch nao representa todos os modulos administrativos nem as regras de permissao do MVP.
- A referencia de login nao define operacao completa, documentos privados, auditoria ou fluxos de homologacao.
- A implementacao preserva a stack PHP/HTML/CSS/JavaScript existente e nao importa React, Tailwind ou codigo gerado sem adaptacao.
