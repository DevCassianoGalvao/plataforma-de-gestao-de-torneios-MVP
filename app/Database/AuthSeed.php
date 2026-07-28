<?php
declare(strict_types=1);

namespace App\Database;

use PDO;

final class AuthSeed
{
    public static function run(PDO $pdo, string $password): void
    {
        if (strlen($password) < 8) {
            throw new \RuntimeException('SEED_DEMO_PASSWORD deve ter pelo menos 8 caracteres.');
        }
        if (getenv('APP_ENV') === 'production') {
            throw new \RuntimeException('Seed de demonstracao bloqueado em producao.');
        }
        $now = date('Y-m-d H:i:s');
        $roles = [
            ['administrator', 'Administrador', 'Acesso global ao sistema.'],
            ['organizer', 'Organizador', 'Opera campeonatos autorizados.'],
            ['team_manager', 'Treinador ou gestor de equipe', 'Opera a equipe autorizada.'],
            ['match_operator', 'Operador de partida', 'Opera partidas atribuidas.'],
            ['communication', 'Comunicacao', 'Gerencia conteudo editorial.'],
        ];
        $permissions = [
            ['system.access', 'Acessar painel', 'Acessa o painel administrativo.', 'sistema'],
            ['system.configure', 'Configurar sistema', 'Configura parametros globais.', 'sistema'],
            ['users.view', 'Visualizar usuarios', 'Lista usuarios.', 'usuarios'],
            ['users.create', 'Criar usuarios', 'Cria usuarios.', 'usuarios'],
            ['users.update', 'Editar usuarios', 'Edita dados de usuarios.', 'usuarios'],
            ['users.deactivate', 'Alterar status de usuarios', 'Ativa, inativa, bloqueia e desbloqueia usuarios.', 'usuarios'],
            ['users.manage_roles', 'Gerenciar perfis', 'Atribui e remove perfis.', 'usuarios'],
            ['audit.view', 'Visualizar auditoria', 'Consulta eventos de auditoria.', 'auditoria'],
            ['championships.view', 'Visualizar campeonatos', 'Consulta campeonatos autorizados.', 'campeonatos'],
            ['championships.manage', 'Gerenciar campeonatos', 'Gerencia campeonatos autorizados.', 'campeonatos'],
            ['championships.create', 'Criar campeonatos', 'Cria campeonatos.', 'campeonatos'],
            ['championships.update', 'Editar campeonatos', 'Edita campeonamentos autorizados.', 'campeonatos'],
            ['championships.archive', 'Arquivar campeonatos', 'Arquiva campeonatos.', 'campeonatos'],
            ['championships.manage_identity', 'Gerenciar identidade do campeonato', 'Gerencia cores e arquivos de identidade.', 'campeonatos'],
            ['championships.manage_assignments', 'Gerenciar organizadores', 'Vincula organizadores a campeonatos.', 'campeonatos'],
            ['teams.view', 'Visualizar equipes', 'Consulta equipes autorizadas.', 'equipes'],
            ['teams.create', 'Criar equipes', 'Cadastra equipes em campeonatos autorizados.', 'equipes'],
            ['teams.update', 'Editar equipes', 'Edita equipes autorizadas.', 'equipes'],
            ['teams.deactivate', 'Inativar equipes', 'Inativa equipes autorizadas.', 'equipes'],
            ['teams.restore', 'Restaurar equipes', 'Restaura equipes arquivadas.', 'equipes'],
            ['teams.manage_assignments', 'Gerenciar responsaveis de equipe', 'Vincula usuarios a equipes.', 'equipes'],
            ['teams.manage_identity', 'Gerenciar identidade da equipe', 'Gerencia escudo e cores da equipe.', 'equipes'],
            ['teams.manage_own', 'Gerenciar propria equipe', 'Edita a propria equipe autorizada.', 'equipes'],
            ['team_staff.view', 'Visualizar comissao tecnica', 'Consulta membros da comissao.', 'comissao'],
            ['team_staff.create', 'Cadastrar comissao tecnica', 'Cadastra membros da comissao.', 'comissao'],
            ['team_staff.update', 'Editar comissao tecnica', 'Edita membros da comissao.', 'comissao'],
            ['team_staff.deactivate', 'Inativar comissao tecnica', 'Inativa membros da comissao.', 'comissao'],
            ['team_staff.manage_own', 'Gerenciar comissao propria', 'Gerencia a comissao da propria equipe.', 'comissao'],
            ['tactical_formations.view', 'Visualizar formacoes taticas', 'Consulta formacoes e slots.', 'formacoes'],
            ['tactical_formations.manage', 'Gerenciar formacoes taticas', 'Gerencia o catalogo de formacoes.', 'formacoes'],
            ['teams.select_default_formation', 'Selecionar formacao padrao', 'Define a formacao padrao da equipe.', 'formacoes'],
            ['athletes.view', 'Visualizar atletas', 'Consulta atletas autorizados.', 'atletas'],
            ['athletes.create', 'Cadastrar atletas', 'Cadastra atletas em equipes autorizadas.', 'atletas'],
            ['athletes.update', 'Editar atletas', 'Edita atletas autorizados.', 'atletas'],
            ['athletes.deactivate', 'Inativar atletas', 'Altera status operacional de atletas.', 'atletas'],
            ['athletes.restore', 'Restaurar atletas', 'Restaura atletas arquivados.', 'atletas'],
            ['athletes.manage_own', 'Gerenciar atletas da propria equipe', 'Gerencia atletas da equipe vinculada.', 'atletas'],
            ['positions.view', 'Visualizar posicoes', 'Consulta o catalogo de posicoes.', 'atletas'],
            ['positions.manage', 'Gerenciar posicoes', 'Gerencia o catalogo de posicoes.', 'atletas'],
            ['athlete_guardians.view', 'Visualizar responsaveis legais', 'Consulta responsaveis de atletas autorizados.', 'atletas'],
            ['athlete_guardians.create', 'Cadastrar responsaveis legais', 'Cadastra responsaveis de atletas.', 'atletas'],
            ['athlete_guardians.update', 'Editar responsaveis legais', 'Edita responsaveis de atletas.', 'atletas'],
            ['athlete_guardians.manage_own', 'Gerenciar responsaveis da propria equipe', 'Gerencia responsaveis da equipe vinculada.', 'atletas'],
            ['athlete_documents.view', 'Visualizar documentos de atletas', 'Consulta documentos privados autorizados.', 'documentos'],
            ['athlete_documents.create', 'Enviar documentos de atletas', 'Envia documentos privados.', 'documentos'],
            ['athlete_documents.update', 'Editar documentos de atletas', 'Atualiza documentos privados.', 'documentos'],
            ['athlete_documents.review', 'Analisar documentos de atletas', 'Aprova ou rejeita documentos.', 'documentos'],
            ['athlete_documents.manage_own', 'Gerenciar documentos da propria equipe', 'Gerencia documentos da equipe vinculada.', 'documentos'],
            ['registrations.view', 'Visualizar inscricoes', 'Consulta inscricoes autorizadas.', 'inscricoes'],
            ['registrations.create', 'Criar inscricoes', 'Cria rascunhos de inscricao.', 'inscricoes'],
            ['registrations.update', 'Editar inscricoes', 'Edita inscricoes autorizadas.', 'inscricoes'],
            ['registrations.submit', 'Enviar inscricoes', 'Envia inscricoes para analise.', 'inscricoes'],
            ['registrations.correct', 'Corrigir inscricoes', 'Corrige pendencias de inscricao.', 'inscricoes'],
            ['registrations.approve', 'Aprovar inscricoes', 'Aprova inscricoes validas.', 'inscricoes'],
            ['registrations.reject', 'Rejeitar inscricoes', 'Rejeita inscricoes com motivo.', 'inscricoes'],
            ['registrations.cancel', 'Cancelar inscricoes', 'Cancela inscricoes autorizadas.', 'inscricoes'],
            ['registrations.manage_own', 'Gerenciar inscricoes da propria equipe', 'Gerencia inscricoes da equipe vinculada.', 'inscricoes'],
            ['rosters.view', 'Visualizar elencos oficiais', 'Consulta atletas aprovados no elenco oficial.', 'inscricoes'],
            ['venues.view', 'Visualizar locais', 'Consulta locais de partidas.', 'tabela'],
            ['venues.create', 'Cadastrar locais', 'Cadastra locais do campeonato.', 'tabela'],
            ['venues.update', 'Editar locais', 'Edita locais do campeonato.', 'tabela'],
            ['phases.view', 'Visualizar fases', 'Consulta fases do campeonato.', 'tabela'],
            ['phases.create', 'Criar fases', 'Cria fases do campeonato.', 'tabela'],
            ['phases.update', 'Editar fases', 'Edita fases do campeonato.', 'tabela'],
            ['phases.publish', 'Publicar fases', 'Publica fases e bloqueia a estrutura.', 'tabela'],
            ['groups.view', 'Visualizar grupos', 'Consulta grupos e equipes distribuidas.', 'tabela'],
            ['groups.create', 'Criar grupos', 'Cria grupos da fase.', 'tabela'],
            ['groups.update', 'Editar grupos', 'Edita grupos e distribuicao.', 'tabela'],
            ['groups.distribute', 'Distribuir equipes', 'Adiciona, move e retira equipes de grupos.', 'tabela'],
            ['groups.publish', 'Publicar grupos', 'Publica grupos do campeonato.', 'tabela'],
            ['schedule.view', 'Visualizar tabela', 'Consulta rodadas, partidas e proximos confrontos.', 'tabela'],
            ['schedule.generate', 'Gerar tabela', 'Gera rodadas e partidas round-robin.', 'tabela'],
            ['schedule.update', 'Editar agenda', 'Edita agenda e registra historico.', 'tabela'],
            ['schedule.postpone', 'Adiar partidas', 'Adia partidas com motivo.', 'tabela'],
            ['schedule.cancel', 'Cancelar partidas', 'Cancela partidas com motivo.', 'tabela'],
            ['lineups.view', 'Visualizar escalacoes', 'Consulta escalacoes autorizadas.', 'escalacoes'],
            ['lineups.create', 'Criar escalacoes', 'Cria rascunhos de escalacao.', 'escalacoes'],
            ['lineups.update', 'Editar escalacoes', 'Edita rascunhos de escalacao.', 'escalacoes'],
            ['lineups.confirm', 'Confirmar escalacoes', 'Confirma titulares, reservas e comissao.', 'escalacoes'],
            ['lineups.reopen', 'Reabrir escalacoes', 'Reabre escalacao confirmada com motivo.', 'escalacoes'],
            ['lineups.manage_own', 'Gerenciar escalacoes da propria equipe', 'Gerencia escalacoes da equipe vinculada.', 'escalacoes'],
            ['seasons.view', 'Visualizar temporadas', 'Consulta temporadas.', 'temporadas'],
            ['seasons.manage', 'Gerenciar temporadas', 'Cria e edita temporadas.', 'temporadas'],
            ['categories.view', 'Visualizar categorias', 'Consulta categorias.', 'categorias'],
            ['categories.manage', 'Gerenciar categorias', 'Cria e edita categorias.', 'categorias'],
            ['regulations.view', 'Visualizar regulamentos', 'Consulta regulamentos.', 'regulamentos'],
            ['regulations.create', 'Criar regulamentos', 'Cria rascunhos de regulamento.', 'regulamentos'],
            ['regulations.update', 'Editar regulamentos', 'Edita rascunhos de regulamento.', 'regulamentos'],
            ['regulations.publish', 'Publicar regulamentos', 'Publica versoes de regulamento.', 'regulamentos'],
            ['regulations.version_history', 'Consultar versoes de regulamento', 'Consulta historico de versoes.', 'regulamentos'],
            ['teams.view', 'Visualizar equipes', 'Consulta equipes.', 'equipes'],
            ['teams.manage', 'Gerenciar equipes', 'Gerencia equipes autorizadas.', 'equipes'],
            ['teams.manage_own', 'Gerenciar propria equipe', 'Gerencia somente sua equipe.', 'equipes'],
            ['athletes.view', 'Visualizar atletas', 'Consulta atletas.', 'atletas'],
            ['athletes.manage', 'Gerenciar atletas', 'Gerencia atletas autorizados.', 'atletas'],
            ['athletes.manage_own', 'Gerenciar atletas da propria equipe', 'Gerencia atletas da equipe autorizada.', 'atletas'],
            ['registrations.review', 'Analisar inscricoes', 'Analisa inscricoes.', 'inscricoes'],
            ['matches.view', 'Visualizar partidas', 'Consulta partidas autorizadas.', 'partidas'],
            ['matches.operate', 'Operar partidas', 'Registra dados de partidas atribuidas.', 'partidas'],
            ['matches.homologate', 'Homologar partidas', 'Homologa resultados autorizados.', 'partidas'],
            ['content.manage', 'Gerenciar conteudo', 'Cria e edita conteudo.', 'conteudo'],
            ['content.publish', 'Publicar conteudo', 'Publica conteudo editorial.', 'conteudo'],
            ['transfers.manage', 'Gerenciar Vai e Vem', 'Gerencia movimentacoes.', 'vai-e-vem'],
        ];
        $roleIds = [];
        $roleStatement = $pdo->prepare('INSERT INTO roles (`key`, name, description, created_at, updated_at) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), updated_at = VALUES(updated_at)');
        foreach ($roles as [$key, $name, $description]) {
            $roleStatement->execute([$key, $name, $description, $now, $now]);
            $roleIds[$key] = self::idByKey($pdo, 'roles', $key);
        }
        $permissionIds = [];
        $permissionStatement = $pdo->prepare('INSERT INTO permissions (`key`, name, description, module, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), module = VALUES(module), updated_at = VALUES(updated_at)');
        foreach ($permissions as [$key, $name, $description, $module]) {
            $permissionStatement->execute([$key, $name, $description, $module, $now, $now]);
            $permissionIds[$key] = self::idByKey($pdo, 'permissions', $key);
        }
        $rolePermissions = [
            'administrator' => array_keys($permissionIds),
            'organizer' => ['championships.view', 'championships.manage', 'championships.create', 'championships.update', 'championships.manage_identity', 'seasons.view', 'categories.view', 'regulations.view', 'regulations.create', 'regulations.update', 'regulations.publish', 'regulations.version_history', 'teams.view', 'teams.create', 'teams.update', 'teams.deactivate', 'teams.restore', 'teams.manage_assignments', 'teams.manage_identity', 'team_staff.view', 'team_staff.create', 'team_staff.update', 'team_staff.deactivate', 'tactical_formations.view', 'tactical_formations.manage', 'teams.select_default_formation', 'athletes.view', 'athletes.create', 'athletes.update', 'athletes.deactivate', 'athletes.restore', 'positions.view', 'positions.manage', 'athlete_guardians.view', 'athlete_guardians.create', 'athlete_guardians.update', 'athlete_documents.view', 'athlete_documents.create', 'athlete_documents.update', 'athlete_documents.review', 'registrations.view', 'registrations.review', 'registrations.correct', 'registrations.approve', 'registrations.reject', 'registrations.cancel', 'rosters.view', 'matches.view', 'matches.homologate', 'venues.view', 'venues.create', 'venues.update', 'phases.view', 'phases.create', 'phases.update', 'phases.publish', 'groups.view', 'groups.create', 'groups.update', 'groups.distribute', 'groups.publish', 'schedule.view', 'schedule.generate', 'schedule.update', 'schedule.postpone', 'schedule.cancel', 'lineups.view', 'content.manage', 'content.publish', 'transfers.manage'],
            'team_manager' => ['teams.view', 'teams.manage_own', 'teams.select_default_formation', 'team_staff.view', 'team_staff.create', 'team_staff.update', 'team_staff.deactivate', 'team_staff.manage_own', 'tactical_formations.view', 'athletes.view', 'athletes.create', 'athletes.manage_own', 'positions.view', 'athlete_guardians.view', 'athlete_guardians.create', 'athlete_guardians.update', 'athlete_guardians.manage_own', 'athlete_documents.view', 'athlete_documents.create', 'athlete_documents.update', 'athlete_documents.manage_own', 'registrations.view', 'registrations.create', 'registrations.update', 'registrations.submit', 'registrations.correct', 'registrations.cancel', 'registrations.manage_own', 'rosters.view', 'matches.view', 'schedule.view', 'lineups.view', 'lineups.create', 'lineups.update', 'lineups.confirm', 'lineups.manage_own'],
            'match_operator' => ['matches.view', 'matches.operate', 'lineups.view'],
            'communication' => ['content.manage', 'content.publish', 'transfers.manage'],
        ];
        $link = $pdo->prepare('INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at) VALUES (?, ?, ?)');
        foreach ($rolePermissions as $roleKey => $keys) {
            foreach ($keys as $permissionKey) {
                $link->execute([$roleIds[$roleKey], $permissionIds[$permissionKey], $now]);
            }
        }
        $users = [
            ['admin@torneios.local', 'Administrador Demo', 'administrator'],
            ['organizador@torneios.local', 'Organizador Demo', 'organizer'],
            ['organizador-sem-acesso@torneios.local', 'Organizador sem acesso Demo', 'organizer'],
            ['treinador@torneios.local', 'Treinador Demo', 'team_manager'],
            ['gestor@torneios.local', 'Gestor Demo', 'team_manager'],
            ['treinador-sem-equipe@torneios.local', 'Treinador sem equipe Demo', 'team_manager'],
            ['operador@torneios.local', 'Operador Demo', 'match_operator'],
            ['comunicacao@torneios.local', 'Comunicacao Demo', 'communication'],
        ];
        $find = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $create = $pdo->prepare('INSERT INTO users (name, email, password_hash, status, created_at, updated_at) VALUES (?, ?, ?, \'active\', ?, ?)');
        $assign = $pdo->prepare('INSERT IGNORE INTO user_roles (user_id, role_id, created_at, created_by) VALUES (?, ?, ?, NULL)');
        foreach ($users as [$email, $name, $roleKey]) {
            $find->execute([$email]);
            $userId = $find->fetchColumn();
            if (!$userId) {
                $create->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $now, $now]);
                $userId = $pdo->lastInsertId();
            }
            $assign->execute([(int) $userId, $roleIds[$roleKey], $now]);
        }
    }

    private static function idByKey(PDO $pdo, string $table, string $key): int
    {
        $statement = $pdo->prepare('SELECT id FROM ' . $table . ' WHERE `key` = ? LIMIT 1');
        $statement->execute([$key]);
        return (int) $statement->fetchColumn();
    }
}
