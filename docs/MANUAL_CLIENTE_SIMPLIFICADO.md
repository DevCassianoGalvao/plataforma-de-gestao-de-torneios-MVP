# Manual do Cliente

## Torneio Online Web App

**Versão:** agosto de 2026  
**Endereço do sistema:** https://www.cassianogalvao.com.br/torneio-online

Este guia explica, em linguagem simples, como administrar um campeonato e como funciona o site público. Ele foi preparado para a rotina do cliente. Os documentos técnicos continuam disponíveis na pasta `docs` do projeto.

## 1. O que o sistema faz

O Torneio Online Web App centraliza:

- cadastro de campeonatos, categorias, equipes e atletas;
- inscrições, elenco oficial e documentos;
- grupos, rodadas, tabela e partidas;
- escalações e formações táticas por partida;
- operação, registros, fotos, aprovação e súmula;
- classificação, artilharia, assistências, cartões e mata-mata;
- notícias, transferências, arbitragem e contatos;
- prestação de contas e exportação de dados;
- site público responsivo do campeonato;
- simulador de resultados, separado dos dados oficiais;
- backups locais e, quando configurado, Google Drive.

## 2. Como entrar

1. Acesse o endereço do sistema.
2. Informe seu e-mail e senha.
3. Se a senha for perdida, peça ao administrador para gerar uma nova senha no cadastro do usuário.
4. Depois do primeiro acesso, altere a senha no seu perfil.

Não compartilhe sua senha. Cada pessoa deve usar o próprio usuário, pois as ações ficam registradas nos logs.

## 3. Perfis de acesso

### Administrador

Tem visão completa do sistema. Pode configurar campeonatos, equipes, atletas, usuários, permissões, partidas, aprovações, conteúdo, backups e prestação de contas.

### Treinador ou gestor de equipe

Gerencia a própria equipe, comissão, atletas, documentos, inscrições, elenco e escalações. Pode solicitar transferências conforme as regras. Não aprova a própria inscrição, partida ou transferência.

### Operador de partida

Opera as partidas atribuídas, registra gols, cartões, substituições, ocorrências, arbitragem, fotos e demais informações da súmula. Finaliza a partida para análise. Não homologa a própria partida.

### Prestação de contas

Acessa somente a área de prestação de contas e os dados liberados para relatórios. Pode baixar arquivos e pacotes de dados, mas não altera a operação esportiva.

## 4. Ordem recomendada de configuração

Para iniciar uma competição, siga esta ordem:

1. Criar o campeonato e a categoria.
2. Configurar identidade, logo, cores, banner e carrossel.
3. Cadastrar regulamento, pontuação, critérios e documentos exigidos.
4. Cadastrar equipes e comissão técnica.
5. Cadastrar atletas, responsáveis e documentos.
6. Enviar e aprovar inscrições.
7. Conferir o elenco oficial.
8. Criar locais, fases, grupos e rodadas.
9. Gerar e revisar a tabela.
10. Cadastrar árbitros e atribuir operadores.
11. Montar escalações por partida.
12. Operar, finalizar e aprovar cada jogo.
13. Publicar notícias e movimentações.
14. Conferir o portal público e os relatórios.

## 5. Painel administrativo

### Visão geral

Mostra um resumo da operação: campeonatos, equipes, atletas, inscrições, partidas e pendências. Use os cartões e os atalhos para encontrar rapidamente o próximo trabalho.

### Notificações

O sino informa novos eventos e pendências importantes. Abra a central para consultar os detalhes e marque os avisos como lidos.

### Logs

Registra acessos, alterações, aprovações, decisões e falhas relevantes. Use os logs para conferir quem executou uma ação e quando ela aconteceu.

### Usuários

O administrador cria usuários, define o perfil, ativa ou inativa o acesso e gera uma nova senha quando necessário. A nova senha deve ser entregue de forma segura e trocada pelo usuário.

## 6. Criar e editar um campeonato

Em **Campeonatos**, crie o campeonato e informe nome, slug, temporada, status e categoria. O administrador pode editar esses dados posteriormente.

Na área de identidade, configure:

- logo do campeonato;
- logo para fundos claros e escuros, quando aplicável;
- favicon;
- banner;
- imagem para compartilhamento;
- cor principal, cor secundária e cor de destaque;
- carrossel de destaques da página inicial.

As cores usam seletor visual. As imagens são verificadas e otimizadas pelo sistema, com conversão para formato leve quando suportado. Como referência, use logo quadrado próximo de 512 x 512 px, banner amplo próximo de 1920 x 720 px e imagem social próxima de 1200 x 630 px.

### Carrossel da página inicial

Em **Identidade do campeonato**, abra **Gerenciar carrossel**. Para cada destaque, informe imagem, título, posição e, se desejar, um link. O botão **Saiba mais** só aparece quando existe um link. Use posições diferentes para ordenar os slides e remova ou arquive itens que não devem mais aparecer.

## 7. Regulamento, categorias e equipes

O regulamento define idade, período de inscrição, pontuação, desempates, quantidade de atletas, documentos, substituições e regras do mata-mata. Configure essas informações antes de abrir as inscrições.

Em **Categorias**, crie ou edite categorias como Adulto, Sub-15 ou outras utilizadas pelo campeonato. A categoria define as validações de idade.

Em **Equipes**, cadastre nome, cidade, escudo, identidade e comissão. O escudo pode ser substituído depois. A equipe também reúne o elenco aprovado e a formação padrão.

## 8. Atletas, responsáveis e documentos

Em **Atletas**, cadastre nome, nome esportivo, foto, nascimento, posições, número, pé dominante, equipe e status. Não é necessário informar IDs manualmente.

O sistema calcula a idade e verifica a compatibilidade com a categoria. Para atleta menor, cadastre o responsável legal e a autorização exigida. Documentos pessoais permanecem privados e não são exibidos no portal.

Os documentos podem estar pendentes, aprovados, rejeitados, vencidos, substituídos ou arquivados. A aprovação exige arquivo válido, tamanho permitido e formato seguro. Arquivos executáveis são bloqueados.

## 9. Inscrições e elenco oficial

O treinador cria a inscrição em rascunho, envia para análise e corrige o que for solicitado. O administrador analisa e decide.

Uma inscrição pode ser rascunho, enviada, em análise, pendente de correção, aprovada, rejeitada, suspensa ou cancelada. Toda transição fica registrada.

Somente atletas aprovados entram no **Elenco oficial**. Apenas atletas do elenco aprovado podem ser confirmados em escalações.

## 10. Grupos, tabela e partidas

Cadastre locais, fases, grupos, equipes e rodadas. O gerador de tabela cria confrontos de turno único ou ida e volta, incluindo folgas quando necessário. Revise conflitos de horário e local antes de publicar.

Cada partida possui equipes, fase, grupo, rodada, data, horário, local, status e observações. Alterações de agenda preservam o histórico e exigem motivo.

## 11. Escalações e campo tático

O treinador escolhe a formação da partida, titulares, reservas, capitão e goleiro. A formação padrão da equipe é apenas uma sugestão: cada jogo pode ter uma configuração própria.

O campo tático distribui os atletas pelos espaços da formação. A posição principal é priorizada, mas posições secundárias e ajustes manuais são permitidos. O sistema avisa quando alguém está fora de posição, sem bloquear a escalação por esse motivo.

Depois da confirmação, alterações comuns ficam bloqueadas. Uma reabertura autorizada exige motivo e fica registrada.

## 12. Operação e aprovação da partida

Na central da partida, o operador registra:

- gols e gols contra;
- assistências;
- cartões amarelos, segundo amarelo e vermelhos;
- substituições, com atleta que sai e que entra;
- ocorrências, horários, prorrogação e pênaltis;
- arbitragem, mesário e demais funções;
- fotos e evidências do jogo.

O minuto é opcional. O sistema calcula o placar a partir dos registros válidos. Pênaltis não entram no placar do tempo normal nem na artilharia.

Antes de finalizar, confira escalações, placar, gols, cartões, substituições, arbitragem, horários, ocorrências e pênaltis. O operador finaliza. O administrador ou responsável autorizado aprova. A aprovação alimenta a classificação, artilharia, assistências e cartões oficiais.

## 13. Súmula e prestação de contas

Após a aprovação, os dados da partida podem gerar a súmula digital baseada na referência aprovada do projeto. A versão da súmula é identificável, privada e preservada.

Na área de **Prestação de contas**, o perfil autorizado pode baixar CSV, Excel, PDF e pacotes de dados disponíveis. Os relatórios oficiais usam somente partidas aprovadas e não incluem simulações.

## 14. Classificação, mata-mata e estatísticas

A classificação é calculada automaticamente a partir dos resultados aprovados e das regras do regulamento. Ela considera jogos, vitórias, empates, derrotas, gols pró, gols contra, saldo, pontos, aproveitamento e critérios de desempate.

Artilharia, assistências e cartões também são alimentados pelos registros da súmula. Não é necessário editar esses números manualmente.

O mata-mata aparece quando houver estrutura e classificados definidos. Somente resultados aprovados avançam vencedores para a próxima fase.

## 15. Notícias, transferências, arbitragem e contatos

### Notícias

Crie rascunhos, envie imagem de capa, revise, agende, publique ou despublique. Apenas notícias publicadas aparecem no site.

### Vai e Vem

O treinador solicita uma movimentação da própria equipe. O administrador analisa, aprova, publica e, quando decidido, aplica o vínculo oficial do atleta. A publicação editorial e a alteração oficial são etapas separadas.

### Arbitragem

Cadastre árbitros com nome e foto e associe suas funções às partidas. A página pública só aparece quando há conteúdo publicado.

### Contatos

O público envia nome, e-mail, telefone, assunto e mensagem. O contato chega ao painel administrativo e gera uma notificação para acompanhamento.

## 16. O site público do campeonato

O portal público mostra somente informações publicadas e aprovadas. Ele pode ter:

- início com fase atual, próximos jogos e últimos resultados;
- classificação e grupos;
- mata-mata;
- equipes e atletas;
- resultados e detalhes da partida;
- artilharia e assistências;
- notícias;
- Vai e Vem;
- regulamento;
- contatos.

O portal é responsivo para celular e computador. Documentos, CPF, telefone, e-mail, endereço, responsáveis legais e observações privadas nunca devem aparecer em páginas públicas.

O escudo, a foto do atleta e os demais elementos visuais são carregados do cadastro. Quando não há imagem, o sistema usa um fallback visual.

## 17. Simulador de resultados

O botão **Simulador** abre uma página separada da classificação oficial. No lado esquerdo fica a classificação simulada; no lado direito, os jogos da rodada.

1. Escolha a rodada ou avance pelas setas.
2. Digite os dois placares de cada partida.
3. Observe a classificação mudar imediatamente.
4. Teste outras rodadas e cenários livremente.
5. Use **Restaurar resultados oficiais** para voltar ao ponto inicial.

O simulador é apenas uma projeção. Ele não altera partidas oficiais, classificação oficial, artilharia, cartões, súmulas, documentos, portal ou prestação de contas.

## 18. Backups

Em **Backups**, é possível criar uma cópia manual e consultar o histórico. A cópia protege o banco de dados e os arquivos enviados.

O armazenamento local funciona dentro do servidor. Para Google Drive, o administrador precisa configurar o destino, o link da pasta e o token autorizado no arquivo `.env` do servidor. O link sozinho não autentica o sistema.

Para backup automático, o cPanel precisa executar o comando agendado no horário escolhido. Isso é chamado de **cron job**: uma tarefa do servidor que roda o comando sem depender de alguém manter a página aberta. Sem cron, o backup manual continua funcionando, mas o automático não será executado.

Antes de apagar uma cópia, confirme se ela não é a única disponível. A restauração deve ser feita por procedimento controlado, preferencialmente em ambiente de teste.

## 19. Dúvidas frequentes

**Por que uma partida não aparece no portal?**  
Verifique se ela foi finalizada e aprovada, se a publicação está habilitada e se pertence ao campeonato correto.

**Por que a classificação não mudou?**  
Confira se o resultado foi aprovado. Simulações nunca alteram a classificação oficial.

**Por que uma notícia não aparece?**  
Ela precisa estar publicada, dentro do campeonato correto e sem data futura de agendamento.

**Por que um atleta não pode ser escalado?**  
Confira equipe, aprovação da inscrição, status ativo, bloqueios e documentos exigidos.

**Por que uma página está vazia?**  
Algumas páginas só aparecem ou exibem conteúdo quando existe registro publicado, como arbitragem, notícias e transferências.

**Quem pode corrigir uma partida?**  
O operador registra e finaliza. A aprovação fica com o administrador ou responsável autorizado. Qualquer reabertura deve ter justificativa.

**O que fazer quando aparece “atualização do banco pendente”?**  
O administrador deve aplicar as migrations no servidor conforme o manual técnico de instalação. Não apague tabelas nem execute comandos destrutivos.

## 20. Boas práticas

- Configure o regulamento antes das inscrições.
- Faça backup antes de mudanças importantes.
- Use imagens leves e com boa proporção.
- Revise escalações e súmulas antes da aprovação.
- Publique somente informações conferidas.
- Não compartilhe senhas ou links de arquivos privados.
- Consulte os logs quando houver dúvida sobre uma alteração.
- Teste o portal no celular antes de divulgar o link.

## 21. Suporte

Ao solicitar ajuda, envie o endereço da tela, o horário aproximado, o usuário utilizado, a ação realizada e o código de suporte exibido. Nunca envie senha, token do Google Drive ou credenciais do banco.

### Documentação complementar

- [Manual do administrador](MANUAL_ADMINISTRADOR.md)
- [Manual do operador de partida](MANUAL_OPERADOR.md)
- [Manual geral do usuário](MANUAL_DO_USUARIO.md)
- [Configuração de backups](APPLICATION_BACKUPS.md)
- [Papéis e permissões](ROLES_AND_PERMISSIONS.md)
