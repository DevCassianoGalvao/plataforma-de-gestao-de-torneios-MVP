ALTER TABLE competition_rounds
    ADD COLUMN round_label VARCHAR(120) NULL AFTER round_number;

UPDATE competition_rounds r
INNER JOIN competition_phases p ON p.id = r.phase_id
SET r.round_label = CASE
    WHEN LOWER(p.name) LIKE '%quart%' THEN 'Quartas de final'
    WHEN LOWER(p.name) LIKE '%semi%' THEN 'Semifinal'
    WHEN LOWER(p.name) LIKE '%final%' THEN 'Final'
    ELSE CONCAT('Rodada ', r.round_number)
END
WHERE r.round_label IS NULL OR r.round_label = '';
