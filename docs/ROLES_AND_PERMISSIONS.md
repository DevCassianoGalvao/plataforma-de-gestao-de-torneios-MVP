# Perfis e Permissoes

## Perfis previstos no PRD

| Perfil | Escopo futuro | Responsabilidade |
|---|---|---|
| Superadministrador | organizacao | configura plataforma e acessos |
| Organizador | campeonatos atribuidos | regula, agenda, homologa e publica |
| Gestor/treinador | equipe e campeonato atribuidos | elenco, documentos e escalaacao |
| Operador de partida | partidas atribuidas | central, eventos e sumula |
| Comunicacao | conteudo publicado | noticias, galerias e Vai e Vem |
| Consulta/prestacao | leitura/exportacao autorizada | indicadores e relatorios |

## Estado atual

Nenhum perfil, login ou permissao de negocio foi implementado. A fundacao somente prepara sessao segura, CSRF e pontos de extensao. Nao usar esta tabela como evidencia de autorizacao existente.

## Regras obrigatorias futuras

- Validar permissao no servidor em toda leitura e mutacao.
- Isolar organizacao, projeto, campeonato, equipe e partida.
- Registrar mutacoes sensiveis e homologacoes em auditoria.
- Nunca confiar em IDs, escopo ou papel enviados pelo navegador.
