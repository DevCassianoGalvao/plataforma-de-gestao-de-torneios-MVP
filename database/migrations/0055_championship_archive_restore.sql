-- Guarda o status anterior ao arquivamento para permitir restaurar o campeonato depois.
ALTER TABLE championships ADD COLUMN status_before_archive VARCHAR(20) NULL AFTER status;
