INSERT INTO categories (name, slug, description, minimum_age, maximum_age, gender_rule, status, created_at, updated_at)
VALUES ('Adulto Masculino', 'adulto-masculino', 'Categoria adulta configuravel.', 18, NULL, 'male', 'active', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    minimum_age = VALUES(minimum_age),
    maximum_age = VALUES(maximum_age),
    gender_rule = VALUES(gender_rule),
    status = VALUES(status),
    updated_at = VALUES(updated_at);

UPDATE championships c
INNER JOIN categories cat ON cat.slug = 'adulto-masculino'
SET c.category_id = cat.id, c.updated_at = NOW()
WHERE c.slug = 'copa-brasil-de-talentos-2026' AND c.deleted_at IS NULL;

UPDATE athletes a
INNER JOIN teams t ON t.id = a.team_id
INNER JOIN championships c ON c.id = t.championship_id
SET a.birth_date = CASE WHEN MOD(a.id, 2) = 0 THEN '2000-03-15' ELSE '2001-10-20' END,
    a.updated_at = NOW()
WHERE c.slug = 'copa-brasil-de-talentos-2026'
  AND a.deleted_at IS NULL
  AND a.birth_date < DATE_SUB(CURDATE(), INTERVAL 18 YEAR)
  AND (a.private_notes LIKE 'Registro ficticio da Etapa 5.%' OR a.private_notes LIKE 'Atleta ficticio adicional para testes de escalacao.%');
