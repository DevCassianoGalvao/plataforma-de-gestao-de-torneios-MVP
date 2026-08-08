CREATE TABLE regulation_competition_rules (
    regulation_id BIGINT UNSIGNED PRIMARY KEY,
    non_local_athlete_limit SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    registration_deadline_days_before_start SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    roster_replacement_notice_days SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    wo_min_players SMALLINT UNSIGNED NOT NULL DEFAULT 7,
    wo_tolerance_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 15,
    wo_counts_for_wins TINYINT(1) NOT NULL DEFAULT 1,
    wo_counts_for_goal_difference TINYINT(1) NOT NULL DEFAULT 1,
    wo_counts_for_goals TINYINT(1) NOT NULL DEFAULT 1,
    wo_eliminates_team TINYINT(1) NOT NULL DEFAULT 0,
    bench_athlete_limit SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    required_first_phase_participation SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    fixed_shirt_number TINYINT(1) NOT NULL DEFAULT 0,
    suspended_next_edition TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_competition_rules_regulation FOREIGN KEY (regulation_id) REFERENCES regulations (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
