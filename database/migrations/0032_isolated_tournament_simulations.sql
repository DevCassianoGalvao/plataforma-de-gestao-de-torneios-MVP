CREATE TABLE simulation_scenarios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    championship_id BIGINT UNSIGNED NOT NULL,
    phase_id BIGINT UNSIGNED NOT NULL,
    group_id BIGINT UNSIGNED NULL,
    round_id BIGINT UNSIGNED NULL,
    name VARCHAR(160) NOT NULL,
    description TEXT NULL,
    assumptions TEXT NULL,
    notes TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    archived_at DATETIME NULL,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_simulation_scenario_championship FOREIGN KEY (championship_id) REFERENCES championships (id),
    CONSTRAINT fk_simulation_scenario_phase FOREIGN KEY (phase_id) REFERENCES competition_phases (id),
    CONSTRAINT fk_simulation_scenario_group FOREIGN KEY (group_id) REFERENCES competition_groups (id) ON DELETE SET NULL,
    CONSTRAINT fk_simulation_scenario_round FOREIGN KEY (round_id) REFERENCES competition_rounds (id) ON DELETE SET NULL,
    CONSTRAINT fk_simulation_scenario_creator FOREIGN KEY (created_by) REFERENCES users (id),
    INDEX idx_simulation_scenarios_championship (championship_id, status, deleted_at),
    INDEX idx_simulation_scenarios_creator (created_by, deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE simulation_matches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    scenario_id BIGINT UNSIGNED NOT NULL,
    reference_match_id BIGINT UNSIGNED NULL,
    phase_id BIGINT UNSIGNED NOT NULL,
    group_id BIGINT UNSIGNED NOT NULL,
    round_id BIGINT UNSIGNED NULL,
    home_team_id BIGINT UNSIGNED NOT NULL,
    away_team_id BIGINT UNSIGNED NOT NULL,
    source_type VARCHAR(20) NOT NULL DEFAULT 'reference',
    home_score SMALLINT UNSIGNED NULL,
    away_score SMALLINT UNSIGNED NULL,
    home_penalties SMALLINT UNSIGNED NULL,
    away_penalties SMALLINT UNSIGNED NULL,
    wo_winner_team_id BIGINT UNSIGNED NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_simulation_match_scenario FOREIGN KEY (scenario_id) REFERENCES simulation_scenarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_simulation_match_reference FOREIGN KEY (reference_match_id) REFERENCES matches (id) ON DELETE SET NULL,
    CONSTRAINT fk_simulation_match_phase FOREIGN KEY (phase_id) REFERENCES competition_phases (id),
    CONSTRAINT fk_simulation_match_group FOREIGN KEY (group_id) REFERENCES competition_groups (id),
    CONSTRAINT fk_simulation_match_round FOREIGN KEY (round_id) REFERENCES competition_rounds (id) ON DELETE SET NULL,
    CONSTRAINT fk_simulation_match_home FOREIGN KEY (home_team_id) REFERENCES teams (id),
    CONSTRAINT fk_simulation_match_away FOREIGN KEY (away_team_id) REFERENCES teams (id),
    CONSTRAINT fk_simulation_match_wo_winner FOREIGN KEY (wo_winner_team_id) REFERENCES teams (id),
    UNIQUE KEY uq_simulation_reference (scenario_id, reference_match_id),
    INDEX idx_simulation_matches_scenario_group (scenario_id, group_id, round_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE simulation_match_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    simulation_match_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    athlete_id BIGINT UNSIGNED NULL,
    event_type VARCHAR(30) NOT NULL,
    minute SMALLINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_simulation_event_match FOREIGN KEY (simulation_match_id) REFERENCES simulation_matches (id) ON DELETE CASCADE,
    CONSTRAINT fk_simulation_event_team FOREIGN KEY (team_id) REFERENCES teams (id),
    CONSTRAINT fk_simulation_event_athlete FOREIGN KEY (athlete_id) REFERENCES athletes (id) ON DELETE SET NULL,
    INDEX idx_simulation_events_match (simulation_match_id, event_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (`key`, name, description, module, created_at, updated_at) VALUES
('simulation.view', 'Visualizar simulacoes internas', 'Consulta cenarios internos sem alterar dados oficiais.', 'simulacoes', NOW(), NOW()),
('simulation.create', 'Criar simulacoes internas', 'Cria cenarios internos isolados.', 'simulacoes', NOW(), NOW()),
('simulation.edit', 'Editar simulacoes internas', 'Altera resultados e premissas de cenarios internos.', 'simulacoes', NOW(), NOW()),
('simulation.delete', 'Excluir simulacoes internas', 'Arquiva e exclui logicamente cenarios internos.', 'simulacoes', NOW(), NOW()),
('simulation.compare', 'Comparar simulacoes internas', 'Compara classificacao oficial e cenarios internos.', 'simulacoes', NOW(), NOW()),
('simulation.manage', 'Gerenciar simulacoes internas', 'Gerencia todos os cenarios internos.', 'simulacoes', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), module = VALUES(module), updated_at = VALUES(updated_at);

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW() FROM roles r INNER JOIN permissions p ON p.`key` IN ('simulation.view','simulation.create','simulation.edit','simulation.delete','simulation.compare','simulation.manage') WHERE r.`key` = 'administrator';
