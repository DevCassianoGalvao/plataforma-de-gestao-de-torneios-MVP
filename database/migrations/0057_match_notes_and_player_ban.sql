-- Observacoes internas da partida (ocorrencias, relatorio do arbitro). Nao vao para o portal publico.
ALTER TABLE matches
    ADD COLUMN internal_notes TEXT NULL,
    ADD COLUMN internal_notes_updated_by BIGINT UNSIGNED NULL,
    ADD COLUMN internal_notes_updated_at DATETIME NULL;

-- Banimento de jogador: some do portal, das listas e da selecao; o historico das partidas ja jogadas fica.
ALTER TABLE athletes
    ADD COLUMN banned_at DATETIME NULL,
    ADD COLUMN banned_reason VARCHAR(500) NULL,
    ADD COLUMN banned_by BIGINT UNSIGNED NULL,
    ADD COLUMN banned_championship_id BIGINT UNSIGNED NULL;

-- Regra de regulamento que habilita o banimento.
ALTER TABLE regulation_advanced_settings ADD COLUMN allow_player_ban TINYINT(1) NOT NULL DEFAULT 0;

-- O regulamento da Copa Brasil de Talentos 2026 passa a permitir banimento
-- (cria a linha de regras avancadas quando a versao ainda nao tem).
INSERT IGNORE INTO regulation_advanced_settings (regulation_id, created_at, updated_at)
SELECT r.id, NOW(), NOW()
FROM regulations r
INNER JOIN championships c ON c.id = r.championship_id
WHERE c.name = 'Copa Brasil de Talentos 2026';

UPDATE regulation_advanced_settings ras
INNER JOIN regulations r ON r.id = ras.regulation_id
INNER JOIN championships c ON c.id = r.championship_id
SET ras.allow_player_ban = 1
WHERE c.name = 'Copa Brasil de Talentos 2026';
