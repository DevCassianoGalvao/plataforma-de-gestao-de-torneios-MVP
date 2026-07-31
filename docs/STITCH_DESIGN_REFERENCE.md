# Referência visual do Stitch

## Consulta

- Projeto: `Football Management Login Interface`
- Identificador consultado via MCP: `projects/11240560855354740609`
- Data da consulta: 2026-07-29
- Origem: projeto privado acessivel pelo servidor MCP do Google Stitch

Nenhuma chave, token, credencial ou dado interno de autenticação foi registrado neste documento.

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

As telas esportivas são referências complementares. O projeto se concentra na linguagem visual e possui maior detalhe no login.

## Elementos extraidos

### Paleta

- Fundo principal noturno: `#0A1422`
- Nivel mais profundo: `#050E1C`
- Superficie: `#101C2B` e `#16202F`
- Superficie elevada: `#212A39` e `#2C3545`
- Texto principal: `#D9E3F7`
- Texto secundário: `#C3C6D7`
- Primária de ação: `#2563EB`
- Primária clara: `#B4C5FF`
- Verde de confirmação: `#22C55E` / `#4AE176`
- Azul de apoio: `#60A5FA`
- Erro: `#FFB4AB`

### Tipografia

- Hanken Grotesk para títulos, placares, números e labels táticos.
- Inter para textos, formulários, tabelas e navegação.
- Display de referência: 48/56px, peso 800.
- Headline principal: 32/40px, peso 700.
- Headline compacto: 24/32px, peso 600.
- Corpo: 16/24px.
- Labels: 12-14px, peso 500-600, com leve espaço entre letras.

### Espaçamento, grid e forma

- Ritmo base de 8px.
- Escala recorrente: 4, 8, 12, 16, 24 e 40px.
- Desktop com grid de 12 colunas e gutter de 24px.
- Tablet com 8 colunas e gutter de 20px.
- Mobile com 4 colunas e margens de 16px.
- Conteúdo administrativo limitado a 1280px.
- Raios pequenos de 2px, padrão de 4px, controles maiores de 8px e destaque de 12px.
- Profundidade por camadas tonais e contornos brancos sutis, sem sombras pesadas.

### Componentes e estados

- Login com painel visual forte, formulário escuro, campos em baixo relevo e foco azul.
- Botões primários azuis com texto claro; secundários em ghost com contorno discreto.
- Cards com camadas de superficie, borda de baixa opacidade e hover com elevação mínima.
- Listas e tabelas com linhas discretas e destaque de linha no hover.
- Badges de status com cor de apoio e texto explicativo, sem depender apenas de cor.
- Campo tático como superficie funcional, com coordenadas e controles visíveis.
- Estados de erro, foco, loading e sucesso previstos no conjunto de componentes do login.

## Aplicação no produto real

- O painel administrativo adota a atmosfera de centro de comando, com sidebar, header contextual, camadas tonais e acentos de campo.
- O login recebe a maior fidelidade de composição, mantendo os fluxos atuais de CSRF, sessão e recuperação de senha.
- O portal público usa a mesma familia tipografica e acentos esportivos, mas possui hierarquia, navegação e superficie próprias para leitura pública.
- As cores cadastradas por campeonato continuam soberanas no portal; o sistema limita contraste e aplica fallback seguro.
- O campo tático, a central da partida e a súmula recebem acabamento de produto, sem transformar operação ou documento em decoração.

## O que não deve ser copiado

- Nenhum logotipo, texto, nome, imagem proprietária ou composição identica do Stitch.
- Nenhum layout de login deve ser duplicado em tabelas, formulários ou páginas públicas.
- Nenhum dado ficticio da referência substitui dados reais do campeonato.
- Gradientes, texturas e acentos só entram quando servem a hierarquia e não reduzem contraste ou leitura.

## Limitações da referência

- O Stitch não representa todos os módulos administrativos nem as regras de permissão do MVP.
- A referência de login não define operação completa, documentos privados, auditoria ou fluxos de homologação.
- A implementação preserva a stack PHP/HTML/CSS/JavaScript existente e não importa React, Tailwind ou código gerado sem adaptação.
