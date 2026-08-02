UPDATE permissions
SET name = 'Gerenciar vínculos do campeonato',
    description = 'Vincula usuários de prestação de contas ao campeonato.',
    updated_at = NOW()
WHERE `key` = 'championships.manage_assignments';
