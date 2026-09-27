-- Baixar a planilha de sumula (modelo oficial, com os atletas das duas equipes da partida)
-- e restrito a administrador, organizador do campeonato e operador de partida -- nao ao
-- treinador/gestor nem a prestacao de contas.
INSERT INTO permissions (`key`, name, description, module, created_at, updated_at)
VALUES ('match_reports.spreadsheet', 'Baixar sumula em planilha', 'Baixa a planilha de sumula no modelo oficial, com os atletas das duas equipes da partida.', 'sumulas', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), module = VALUES(module), updated_at = VALUES(updated_at);

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW()
FROM roles r
INNER JOIN permissions p ON p.`key` = 'match_reports.spreadsheet'
WHERE r.`key` IN ('administrator', 'match_operator', 'organizer');
