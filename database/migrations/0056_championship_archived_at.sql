-- Marca de arquivamento usada para esconder o campeonato (e tudo ligado a ele) de todas as listas.
ALTER TABLE championships ADD COLUMN archived_at DATETIME NULL AFTER status_before_archive;
UPDATE championships SET archived_at = updated_at WHERE status = 'archived' AND archived_at IS NULL;
