# Equipes e Comissão Técnica

## Escopo

A Etapa 4 entrega o cadastro administrativo de equipes vinculadas a campeonamentos, seus responsáveis, membros da comissão técnica, identidade visual básica, status e formação tática padrão. A página da equipe também exibe o elenco oficial aprovado e atalhos para inscrições e partidas.

## Equipes

Uma equipe possui campeonato, nome, nome curto, slug, sigla, descrição, cidade, estado, cores primária e secundária, escudo privado, status, autor e timestamps. O nome e o slug são unicos dentro do campeonato. O seletor de campeonato mostra somente campeonamentos autorizados; nenhum formulário pede `championship_id` digitado.

Status válidos: `draft`, `active`, `inactive`, `withdrawn` e `archived`. As transições passam por `TeamStatusService`, e a alteração registra auditoria. Equipes retiradas ou arquivadas permanecem no histórico e não são apagadas fisicamente.

## Responsáveis e escopo

`team_user_assignments` registra vínculos `manager`, `head_coach`, `assistant_coach` e `viewer`, com início, fim, status e autor. O mesmo usuário pode ter mais de uma equipe quando cada acesso for concedido por vínculo explícito.

- Administrador acessa e administra todas as equipes.
- Organizador acessa equipes de campeonamentos aos quais está vinculado.
- Treinador ou gestor acessa somente equipes com vínculo ativo e pode editar o escopo operacional permitido.
- Operador e comunicação recebem `403` neste módulo por padrão.

Responsáveis são atribuídos a partir de usuários ativos existentes. Atribuições, encerramentos e alterações geram auditoria. Um treinador ou gestor não pode mudar o campeonato da própria equipe, acessar outra equipe ou atribuir responsáveis administrativos.

## Comissão técnica

`staff_roles` e um catálogo idempotente com as funções: treinador, auxiliar técnico, preparador fisico, preparador de goleiros, fisioterapeuta, medico, massagista, dirigente, supervisor, roupeiro e outro. `team_staff` permite nome, nome de exibição, email, telefone, foto privada, identificação profissional, status, período e observações; `user_id` é opcional, portanto um membro pode existir sem login.

O campo `document_number` permanece na estrutura para uma futura implementação protegida, mas não e solicitado nem armazenado nesta etapa. Não ha coleta de documento pessoal sem a camada de proteção correspondente.

## Uploads e auditoria

Escudo da equipe e foto de membro aceitam PNG, JPG, JPEG e WEBP. O MIME, a extensao e o tamanho são validados; imagens são corrigidas, reduzidas proporcionalmente e convertidas para WebP. SVG e executáveis são recusados. Os arquivos recebem nomes aleatórios, ficam fora da área pública e são acessiveis somente por rota autenticada e autorizada. Substituições usam armazenamento privado e limpeza segura do arquivo anterior.

A auditoria cobre criação, edição, identidade, status, atribuição e encerramento de responsáveis, criação/edição/status da comissão e alteração da formação padrão. A interface mostra textos compreensiveis, sem exibir metadata JSON bruto.

## Integração atual

Atletas, documentos, inscrições, elenco oficial, grupos, rodadas, tabela, partidas, escalações, cartões, suspensões e portal público possuem módulos próprios e são acessados pelo painel conforme a permissão do usuário.
