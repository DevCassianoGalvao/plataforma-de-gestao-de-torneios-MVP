-- Garante no banco de producao todas as permissoes do perfil team_manager.
-- Migrations antigas (ex.: 0024) apenas vinculavam permissoes que so eram criadas pelo AuthSeed (dev),
-- entao em producao o treinador ficava sem elas (ex.: trocar o escudo). Idempotente.
INSERT INTO permissions (`key`, name, description, module, created_at, updated_at)
VALUES
    ('teams.view', 'Visualizar equipes', 'Consulta equipes.', 'equipes', NOW(), NOW()),
    ('teams.manage_own', 'Gerenciar propria equipe', 'Gerencia somente sua equipe.', 'equipes', NOW(), NOW()),
    ('teams.select_default_formation', 'Selecionar formacao padrao', 'Define a formacao padrao da equipe.', 'formacoes', NOW(), NOW()),
    ('team_staff.view', 'Visualizar comissao tecnica', 'Consulta membros da comissao.', 'comissao', NOW(), NOW()),
    ('team_staff.create', 'Cadastrar comissao tecnica', 'Cadastra membros da comissao.', 'comissao', NOW(), NOW()),
    ('team_staff.update', 'Editar comissao tecnica', 'Edita membros da comissao.', 'comissao', NOW(), NOW()),
    ('team_staff.deactivate', 'Inativar comissao tecnica', 'Inativa membros da comissao.', 'comissao', NOW(), NOW()),
    ('team_staff.manage_own', 'Gerenciar comissao propria', 'Gerencia a comissao da propria equipe.', 'comissao', NOW(), NOW()),
    ('tactical_formations.view', 'Visualizar formacoes taticas', 'Consulta formacoes e slots.', 'formacoes', NOW(), NOW()),
    ('athletes.view', 'Visualizar atletas', 'Consulta atletas.', 'atletas', NOW(), NOW()),
    ('athletes.create', 'Cadastrar atletas', 'Cadastra atletas em equipes autorizadas.', 'atletas', NOW(), NOW()),
    ('athletes.manage_own', 'Gerenciar atletas da propria equipe', 'Gerencia atletas da equipe autorizada.', 'atletas', NOW(), NOW()),
    ('positions.view', 'Visualizar posicoes', 'Consulta o catalogo de posicoes.', 'atletas', NOW(), NOW()),
    ('athlete_guardians.view', 'Visualizar responsaveis legais', 'Consulta responsaveis de atletas autorizados.', 'atletas', NOW(), NOW()),
    ('athlete_guardians.create', 'Cadastrar responsaveis legais', 'Cadastra responsaveis de atletas.', 'atletas', NOW(), NOW()),
    ('athlete_guardians.update', 'Editar responsaveis legais', 'Edita responsaveis de atletas.', 'atletas', NOW(), NOW()),
    ('athlete_guardians.manage_own', 'Gerenciar responsaveis da propria equipe', 'Gerencia responsaveis da equipe vinculada.', 'atletas', NOW(), NOW()),
    ('athlete_documents.view', 'Visualizar documentos de atletas', 'Consulta documentos privados autorizados.', 'documentos', NOW(), NOW()),
    ('athlete_documents.create', 'Enviar documentos de atletas', 'Envia documentos privados.', 'documentos', NOW(), NOW()),
    ('athlete_documents.update', 'Editar documentos de atletas', 'Atualiza documentos privados.', 'documentos', NOW(), NOW()),
    ('athlete_documents.manage_own', 'Gerenciar documentos da propria equipe', 'Gerencia documentos da equipe vinculada.', 'documentos', NOW(), NOW()),
    ('registrations.view', 'Visualizar inscricoes', 'Consulta inscricoes autorizadas.', 'inscricoes', NOW(), NOW()),
    ('registrations.create', 'Criar inscricoes', 'Cria rascunhos de inscricao.', 'inscricoes', NOW(), NOW()),
    ('registrations.update', 'Editar inscricoes', 'Edita inscricoes autorizadas.', 'inscricoes', NOW(), NOW()),
    ('registrations.submit', 'Enviar inscricoes', 'Envia inscricoes para analise.', 'inscricoes', NOW(), NOW()),
    ('registrations.correct', 'Corrigir inscricoes', 'Corrige pendencias de inscricao.', 'inscricoes', NOW(), NOW()),
    ('registrations.cancel', 'Cancelar inscricoes', 'Cancela inscricoes autorizadas.', 'inscricoes', NOW(), NOW()),
    ('registrations.manage_own', 'Gerenciar inscricoes da propria equipe', 'Gerencia inscricoes da equipe vinculada.', 'inscricoes', NOW(), NOW()),
    ('rosters.view', 'Visualizar elencos oficiais', 'Consulta atletas aprovados no elenco oficial.', 'inscricoes', NOW(), NOW()),
    ('matches.view', 'Visualizar partidas', 'Consulta partidas autorizadas.', 'partidas', NOW(), NOW()),
    ('schedule.view', 'Visualizar tabela', 'Consulta rodadas, partidas e proximos confrontos.', 'tabela', NOW(), NOW()),
    ('lineups.view', 'Visualizar escalacoes', 'Consulta escalacoes autorizadas.', 'escalacoes', NOW(), NOW()),
    ('lineups.create', 'Criar escalacoes', 'Cria rascunhos de escalacao.', 'escalacoes', NOW(), NOW()),
    ('lineups.update', 'Editar escalacoes', 'Edita rascunhos de escalacao.', 'escalacoes', NOW(), NOW()),
    ('lineups.confirm', 'Confirmar escalacoes', 'Confirma titulares, reservas e comissao.', 'escalacoes', NOW(), NOW()),
    ('lineups.manage_own', 'Gerenciar escalacoes da propria equipe', 'Gerencia escalacoes da equipe vinculada.', 'escalacoes', NOW(), NOW()),
    ('match_operation.view', 'Visualizar central da partida', 'Consulta a central operacional autorizada.', 'partidas', NOW(), NOW()),
    ('discipline.view', 'Visualizar disciplina', 'Consulta cartoes, acumulacoes e suspensoes autorizadas.', 'disciplina', NOW(), NOW()),
    ('suspensions.view', 'Visualizar suspensoes', 'Consulta suspensoes e cumprimento.', 'disciplina', NOW(), NOW()),
    ('standings.view', 'Visualizar classificacao', 'Consulta classificacoes autorizadas.', 'classificacao', NOW(), NOW()),
    ('match_reports.view', 'Visualizar sumulas', 'Consulta sumulas autorizadas.', 'sumulas', NOW(), NOW()),
    ('match_reports.download', 'Baixar sumulas', 'Baixa PDFs de sumulas autorizadas.', 'sumulas', NOW(), NOW()),
    ('transfers.request', 'Solicitar Vai e Vem', 'Cria e acompanha solicitacoes de transferencia da propria equipe.', 'vai-e-vem', NOW(), NOW()),
    ('teams.manage_identity', 'Gerenciar identidade da equipe', 'Gerencia escudo e cores da equipe.', 'equipes', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), module = VALUES(module), updated_at = VALUES(updated_at);

-- Treinador/Gestor recebe o conjunto completo.
INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW()
FROM roles r
INNER JOIN permissions p ON p.`key` IN ('teams.view', 'teams.manage_own', 'teams.select_default_formation', 'team_staff.view', 'team_staff.create', 'team_staff.update', 'team_staff.deactivate', 'team_staff.manage_own', 'tactical_formations.view', 'athletes.view', 'athletes.create', 'athletes.manage_own', 'positions.view', 'athlete_guardians.view', 'athlete_guardians.create', 'athlete_guardians.update', 'athlete_guardians.manage_own', 'athlete_documents.view', 'athlete_documents.create', 'athlete_documents.update', 'athlete_documents.manage_own', 'registrations.view', 'registrations.create', 'registrations.update', 'registrations.submit', 'registrations.correct', 'registrations.cancel', 'registrations.manage_own', 'rosters.view', 'matches.view', 'schedule.view', 'lineups.view', 'lineups.create', 'lineups.update', 'lineups.confirm', 'lineups.manage_own', 'match_operation.view', 'discipline.view', 'suspensions.view', 'standings.view', 'match_reports.view', 'match_reports.download', 'transfers.request', 'teams.manage_identity')
WHERE r.`key` = 'team_manager';

-- Administrador e organizador tambem recebem (superconjunto).
INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW()
FROM roles r
INNER JOIN permissions p ON p.`key` IN ('teams.view', 'teams.manage_own', 'teams.select_default_formation', 'team_staff.view', 'team_staff.create', 'team_staff.update', 'team_staff.deactivate', 'team_staff.manage_own', 'tactical_formations.view', 'athletes.view', 'athletes.create', 'athletes.manage_own', 'positions.view', 'athlete_guardians.view', 'athlete_guardians.create', 'athlete_guardians.update', 'athlete_guardians.manage_own', 'athlete_documents.view', 'athlete_documents.create', 'athlete_documents.update', 'athlete_documents.manage_own', 'registrations.view', 'registrations.create', 'registrations.update', 'registrations.submit', 'registrations.correct', 'registrations.cancel', 'registrations.manage_own', 'rosters.view', 'matches.view', 'schedule.view', 'lineups.view', 'lineups.create', 'lineups.update', 'lineups.confirm', 'lineups.manage_own', 'match_operation.view', 'discipline.view', 'suspensions.view', 'standings.view', 'match_reports.view', 'match_reports.download', 'transfers.request', 'teams.manage_identity')
WHERE r.`key` IN ('administrator', 'organizer');
