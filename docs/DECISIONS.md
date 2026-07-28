# Decisões

## D-001 — Dependências mínimas

Composer fornece autoload PSR-4. O carregamento de `.env` é interno para manter instalação cPanel simples e evitar dependência obrigatória.

## D-002 — Persistência de regras

Parâmetros esportivos são JSON versionável em `tournament_settings`. A seed da Copa Brasil de Talentos é configuração de desenvolvimento, não constante global.

## D-003 — Primeiro escopo de autorização

O seed cria superadministrador. O código já exige autenticação e permite atribuições futuras por organização/projeto/campeonato/equipe. Usuários sem atribuição não recebem acesso administrativo.

## D-004 — Exclusão

CRUD usa `deleted_at`; registros referenciados não são apagados fisicamente.

## D-005 — Tema

Somente tokens permitidos são persistidos. Cores são validadas como hexadecimal e aplicadas com contraste base conservador.

## D-006 — Privacidade

Portal público mostra somente nome público e posição quando aplicável. CPF, documentos, WhatsApp e nascimento completo ficam fora das respostas públicas.
