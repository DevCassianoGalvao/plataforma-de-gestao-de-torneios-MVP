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
            'organizer' => ['championships.view', 'championships.manage', 'teams.view', 'teams.manage', 'athletes.view', 'registrations.review', 'matches.view', 'matches.homologate', 'content.manage', 'content.publish', 'transfers.manage'],
            'team_manager' => ['teams.view', 'teams.manage_own', 'athletes.view', 'athletes.manage_own', 'matches.view'],
            'match_operator' => ['matches.view', 'matches.operate'],
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
            ['treinador@torneios.local', 'Treinador Demo', 'team_manager'],
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
