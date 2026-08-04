CREATE TABLE regulation_advanced_settings (
    regulation_id BIGINT UNSIGNED PRIMARY KEY,
    maximum_staff_members SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    maximum_teams SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    allow_registration_after_start TINYINT(1) NOT NULL DEFAULT 0,
    registration_requires_approval TINYINT(1) NOT NULL DEFAULT 1,
    require_complete_documents TINYINT(1) NOT NULL DEFAULT 1,
    require_minor_authorization TINYINT(1) NOT NULL DEFAULT 1,
    roster_change_limit SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    roster_change_deadline DATE NULL,
    roster_change_phase_limit BIGINT UNSIGNED NULL,
    transfers_enabled TINYINT(1) NOT NULL DEFAULT 1,
    transfers_blocked TINYINT(1) NOT NULL DEFAULT 0,
    block_athlete_played_other_team TINYINT(1) NOT NULL DEFAULT 0,
    allow_administrative_exception TINYINT(1) NOT NULL DEFAULT 0,
    exception_reason_required TINYINT(1) NOT NULL DEFAULT 1,
    abandoned_match_rule VARCHAR(30) NOT NULL DEFAULT 'administrative_decision',
    cancelled_match_rule VARCHAR(30) NOT NULL DEFAULT 'administrative_decision',
    postponed_match_rule VARCHAR(30) NOT NULL DEFAULT 'reschedule',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_advanced_regulation FOREIGN KEY (regulation_id) REFERENCES regulations (id) ON DELETE CASCADE,
    CONSTRAINT fk_advanced_phase FOREIGN KEY (roster_change_phase_limit) REFERENCES competition_phases (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE regulation_eligibility_rules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    regulation_id BIGINT UNSIGNED NOT NULL,
    source_phase_id BIGINT UNSIGNED NOT NULL,
    destination_phase_id BIGINT UNSIGNED NOT NULL,
    minimum_participations SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    participation_type VARCHAR(20) NOT NULL DEFAULT 'listed',
    registration_approved_before DATE NULL,
    require_no_suspension TINYINT(1) NOT NULL DEFAULT 1,
    require_same_team TINYINT(1) NOT NULL DEFAULT 1,
    require_complete_documents TINYINT(1) NOT NULL DEFAULT 0,
    allow_exception TINYINT(1) NOT NULL DEFAULT 0,
    release_permission VARCHAR(80) NOT NULL DEFAULT 'regulations.grant_exception',
    reason_required TINYINT(1) NOT NULL DEFAULT 1,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_eligibility_route (regulation_id, source_phase_id, destination_phase_id),
    CONSTRAINT fk_eligibility_regulation FOREIGN KEY (regulation_id) REFERENCES regulations (id) ON DELETE CASCADE,
    CONSTRAINT fk_eligibility_source FOREIGN KEY (source_phase_id) REFERENCES competition_phases (id),
    CONSTRAINT fk_eligibility_destination FOREIGN KEY (destination_phase_id) REFERENCES competition_phases (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE regulation_eligibility_exceptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    championship_id BIGINT UNSIGNED NOT NULL,
    athlete_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    rule_id BIGINT UNSIGNED NULL,
    match_id BIGINT UNSIGNED NULL,
    phase_id BIGINT UNSIGNED NULL,
    ignored_rule VARCHAR(80) NOT NULL,
    reason TEXT NOT NULL,
    expires_at DATETIME NULL,
    granted_by BIGINT UNSIGNED NOT NULL,
    granted_at DATETIME NOT NULL,
    revoked_at DATETIME NULL,
    revoked_by BIGINT UNSIGNED NULL,
    revoke_reason TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_eligibility_exception_lookup (championship_id, athlete_id, team_id, revoked_at, expires_at),
    CONSTRAINT fk_exception_championship FOREIGN KEY (championship_id) REFERENCES championships (id),
    CONSTRAINT fk_exception_athlete FOREIGN KEY (athlete_id) REFERENCES athletes (id),
    CONSTRAINT fk_exception_team FOREIGN KEY (team_id) REFERENCES teams (id),
    CONSTRAINT fk_exception_rule FOREIGN KEY (rule_id) REFERENCES regulation_eligibility_rules (id) ON DELETE SET NULL,
    CONSTRAINT fk_exception_match FOREIGN KEY (match_id) REFERENCES matches (id) ON DELETE SET NULL,
    CONSTRAINT fk_exception_phase FOREIGN KEY (phase_id) REFERENCES competition_phases (id) ON DELETE SET NULL,
    CONSTRAINT fk_exception_granted FOREIGN KEY (granted_by) REFERENCES users (id),
    CONSTRAINT fk_exception_revoked FOREIGN KEY (revoked_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE regulation_change_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    regulation_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(40) NOT NULL,
    previous_values JSON NULL,
    new_values JSON NULL,
    changed_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_regulation_change_logs (regulation_id, created_at),
    CONSTRAINT fk_change_log_regulation FOREIGN KEY (regulation_id) REFERENCES regulations (id) ON DELETE CASCADE,
    CONSTRAINT fk_change_log_user FOREIGN KEY (changed_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (`key`, name, description, module, created_at, updated_at) VALUES
('regulations.manage_eligibility', 'Gerenciar elegibilidade', 'Configura regras de elegibilidade entre fases.', 'regulamentos', NOW(), NOW()),
('regulations.grant_exception', 'Liberar excecao de elegibilidade', 'Libera excecoes administrativas justificadas.', 'regulamentos', NOW(), NOW())
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), updated_at=VALUES(updated_at);

INSERT INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW() FROM roles r INNER JOIN permissions p ON p.`key` IN ('regulations.manage_eligibility','regulations.grant_exception') WHERE r.`key`='administrator'
ON DUPLICATE KEY UPDATE created_at=VALUES(created_at);
