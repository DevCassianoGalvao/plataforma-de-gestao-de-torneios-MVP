<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AccountabilityRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function championshipsFor(int $userId, bool $administrator): array
    {
        if ($administrator) return $this->pdo->query("SELECT id, name, slug FROM championships WHERE deleted_at IS NULL ORDER BY name")->fetchAll();
        $statement = $this->pdo->prepare("SELECT c.id, c.name, c.slug FROM championships c INNER JOIN championship_user_assignments a ON a.championship_id = c.id WHERE a.user_id = ? AND a.assignment_type = 'accountability' AND c.deleted_at IS NULL ORDER BY c.name");
        $statement->execute([$userId]);
        return $statement->fetchAll();
    }

    public function allowed(int $championshipId, int $userId, bool $administrator): bool
    {
        if ($administrator) return true;
        $statement = $this->pdo->prepare("SELECT 1 FROM championship_user_assignments WHERE championship_id = ? AND user_id = ? AND assignment_type = 'accountability'");
        $statement->execute([$championshipId, $userId]);
        return (bool) $statement->fetchColumn();
    }

    public function settings(int $championshipId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM championship_accountability_settings WHERE championship_id = ? LIMIT 1');
        $statement->execute([$championshipId]);
        return $statement->fetch() ?: [
            'championship_id' => $championshipId,
            'require_current_report' => 1,
            'require_signed_report' => 0,
            'require_approved_evidence' => 0,
        ];
    }

    public function saveSettings(int $championshipId, int $userId, array $data): void
    {
        $now = date('Y-m-d H:i:s');
        $statement = $this->pdo->prepare('INSERT INTO championship_accountability_settings (championship_id, require_current_report, require_signed_report, require_approved_evidence, updated_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE require_current_report = VALUES(require_current_report), require_signed_report = VALUES(require_signed_report), require_approved_evidence = VALUES(require_approved_evidence), updated_by = VALUES(updated_by), updated_at = VALUES(updated_at)');
        $statement->execute([$championshipId, !empty($data['require_current_report']) ? 1 : 0, !empty($data['require_signed_report']) ? 1 : 0, !empty($data['require_approved_evidence']) ? 1 : 0, $userId, $now, $now]);
    }

    public function summary(int $id): array
    {
        $queries = [
            'partidas_aprovadas' => "SELECT COUNT(*) FROM matches WHERE championship_id = ? AND status = 'homologated'",
            'partidas_publicadas' => "SELECT COUNT(*) FROM matches m INNER JOIN match_publications mp ON mp.match_id = m.id AND mp.status = 'published' WHERE m.championship_id = ? AND m.status = 'homologated'",
            'partidas_completas' => "SELECT COUNT(*) FROM matches m WHERE m.championship_id = ? AND m.status = 'homologated' AND EXISTS (SELECT 1 FROM match_reports mr INNER JOIN match_report_versions v ON v.id = mr.current_version_id WHERE mr.match_id = m.id)",
            'sumulas' => "SELECT COUNT(*) FROM match_report_versions v INNER JOIN match_reports r ON r.id = v.match_report_id INNER JOIN matches m ON m.id = r.match_id WHERE m.championship_id = ? AND m.status = 'homologated'",
            'sumulas_assinadas' => "SELECT COUNT(*) FROM match_report_versions v INNER JOIN match_reports r ON r.id = v.match_report_id INNER JOIN matches m ON m.id = r.match_id WHERE m.championship_id = ? AND m.status = 'homologated' AND v.signed_storage_path IS NOT NULL",
            'evidencias' => "SELECT COUNT(*) FROM match_media mm INNER JOIN matches m ON m.id=mm.match_id WHERE mm.championship_id = ? AND m.status = 'homologated' AND mm.status = 'approved' AND mm.review_status = 'approved' AND mm.deleted_at IS NULL",
            'evidencias_pendentes' => "SELECT COUNT(*) FROM match_media WHERE championship_id = ? AND review_status IN ('draft','submitted') AND deleted_at IS NULL",
            'evidencias_rejeitadas' => "SELECT COUNT(*) FROM match_media WHERE championship_id = ? AND review_status = 'rejected' AND deleted_at IS NULL",
            'dias_evento' => "SELECT COUNT(*) FROM event_days WHERE championship_id = ? AND deleted_at IS NULL AND status = 'active'",
            'evidencias_dias_evento' => "SELECT COUNT(*) FROM event_day_media WHERE championship_id = ? AND status = 'approved' AND review_status = 'approved' AND deleted_at IS NULL",
            'excecoes_documentais' => "SELECT COUNT(*) FROM match_evidence_exceptions ex INNER JOIN matches m ON m.id=ex.match_id WHERE m.championship_id = ?",
            'atletas_aprovados' => "SELECT COUNT(*) FROM athlete_registrations WHERE championship_id = ? AND status = 'approved'",
        ];
        $result = [];
        foreach ($queries as $key => $sql) {
            $statement = $this->pdo->prepare($sql);
            $statement->execute([$id]);
            $result[$key] = (int) $statement->fetchColumn();
        }
        $result['partidas_pendentes'] = max(0, $result['partidas_aprovadas'] - $result['partidas_completas']);
        return $result;
    }

    public function filterOptions(int $championshipId): array
    {
        $load = function (string $sql) use ($championshipId): array {
            $statement = $this->pdo->prepare($sql);
            $statement->execute([$championshipId]);
            return $statement->fetchAll();
        };
        return [
            'phases' => $load('SELECT DISTINCT p.id, p.name FROM competition_phases p INNER JOIN matches m ON m.phase_id = p.id WHERE m.championship_id = ? ORDER BY p.name'),
            'groups' => $load('SELECT DISTINCT g.id, g.name FROM competition_groups g INNER JOIN matches m ON m.group_id = g.id WHERE m.championship_id = ? ORDER BY g.name'),
            'rounds' => $load("SELECT DISTINCT r.id, r.round_number, COALESCE(NULLIF(r.round_label, ''), CONCAT('Rodada ', r.round_number)) AS round_label FROM competition_rounds r INNER JOIN matches m ON m.round_id = r.id WHERE m.championship_id = ? ORDER BY r.round_number"),
            'teams' => $load('SELECT DISTINCT t.id, t.name FROM teams t INNER JOIN matches m ON (m.home_team_id = t.id OR m.away_team_id = t.id) WHERE m.championship_id = ? ORDER BY t.name'),
            'venues' => $load('SELECT DISTINCT v.id, v.name, v.city FROM venues v INNER JOIN matches m ON m.venue_id = v.id WHERE m.championship_id = ? ORDER BY v.city, v.name'),
            'cities' => $load("SELECT DISTINCT v.city AS name FROM venues v INNER JOIN matches m ON m.venue_id = v.id WHERE m.championship_id = ? AND v.city IS NOT NULL AND v.city <> '' ORDER BY v.city"),
            'event_days' => $load('SELECT ed.id, ed.name, ed.event_date, v.name AS venue_name, v.city FROM event_days ed LEFT JOIN venues v ON v.id = ed.venue_id WHERE ed.championship_id = ? AND ed.deleted_at IS NULL AND ed.status = \'active\' ORDER BY ed.event_date, ed.id'),
        ];
    }

    public function matches(int $championshipId, array $filters = []): array
    {
        $where = ["m.championship_id = ?", "m.status = 'homologated'"];
        $params = [$championshipId];
        $equals = ['phase_id' => 'm.phase_id', 'group_id' => 'm.group_id', 'round_id' => 'm.round_id', 'venue_id' => 'm.venue_id'];
        foreach ($equals as $key => $column) {
            if (isset($filters[$key]) && ctype_digit((string) $filters[$key]) && (int) $filters[$key] > 0) { $where[] = $column . ' = ?'; $params[] = (int) $filters[$key]; }
        }
        if (isset($filters['team_id']) && ctype_digit((string) $filters['team_id']) && (int) $filters['team_id'] > 0) { $where[] = '(m.home_team_id = ? OR m.away_team_id = ?)'; $params[] = (int) $filters['team_id']; $params[] = (int) $filters['team_id']; }
        if (!empty($filters['from'])) { $where[] = 'm.match_date >= ?'; $params[] = substr((string) $filters['from'], 0, 10); }
        if (!empty($filters['to'])) { $where[] = 'm.match_date <= ?'; $params[] = substr((string) $filters['to'], 0, 10); }
        if (!empty($filters['city'])) { $where[] = 'venue.city = ?'; $params[] = (string) $filters['city']; }
        if (!empty($filters['event_day_id']) && ctype_digit((string) $filters['event_day_id']) && (int) $filters['event_day_id'] > 0) { $where[] = 'EXISTS (SELECT 1 FROM event_days edf WHERE edf.id = ? AND edf.championship_id = m.championship_id AND edf.event_date = m.match_date AND (edf.venue_id IS NULL OR edf.venue_id = m.venue_id) AND edf.deleted_at IS NULL)'; $params[] = (int) $filters['event_day_id']; }
        $sql = "SELECT m.id, m.phase_id, m.group_id, m.round_id, m.match_date, m.match_time, p.name AS fase, g.name AS grupo, r.round_number, COALESCE(NULLIF(r.round_label, ''), CONCAT('Rodada ', r.round_number)) AS rodada_label, ht.id AS mandante_id, ht.name AS mandante, at.id AS visitante_id, at.name AS visitante, venue.name AS venue_name, venue.city AS venue_city, mr.current_version_id, rv.storage_path AS sumula_path, rv.signed_storage_path, mp.status AS publication_status FROM matches m INNER JOIN competition_phases p ON p.id = m.phase_id INNER JOIN competition_groups g ON g.id = m.group_id INNER JOIN competition_rounds r ON r.id = m.round_id INNER JOIN teams ht ON ht.id = m.home_team_id INNER JOIN teams at ON at.id = m.away_team_id LEFT JOIN venues venue ON venue.id = m.venue_id LEFT JOIN match_reports mr ON mr.match_id = m.id LEFT JOIN match_report_versions rv ON rv.id = mr.current_version_id LEFT JOIN match_publications mp ON mp.match_id = m.id WHERE " . implode(' AND ', $where) . ' ORDER BY m.match_date, m.match_time, m.id';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        $rows = $statement->fetchAll();
        foreach ($rows as &$row) {
            $row['document_status'] = $this->documentStatus($championshipId, (int) $row['id']);
            if (($filters['document_status'] ?? '') !== '' && $filters['document_status'] !== $row['document_status']) { $row['_skip'] = true; }
        }
        unset($row);
        return array_values(array_filter($rows, static fn (array $row): bool => empty($row['_skip'])));
    }

    public function documentStatus(int $championshipId, int $matchId): string
    {
        $settings = $this->settings($championshipId);
        $blockers = [];
        $report = $this->pdo->prepare('SELECT v.id, v.signed_storage_path FROM match_reports r LEFT JOIN match_report_versions v ON v.id = r.current_version_id WHERE r.match_id = ? LIMIT 1');
        $report->execute([$matchId]);
        $version = $report->fetch();
        if (!empty($settings['require_current_report']) && !$version) $blockers[] = 'sumula';
        if (!empty($settings['require_signed_report']) && (!$version || empty($version['signed_storage_path']))) $blockers[] = 'assinatura';
        if (!empty($settings['require_approved_evidence'])) {
            $evidence = $this->pdo->prepare("SELECT COUNT(*) FROM match_media WHERE match_id = ? AND deleted_at IS NULL AND status = 'approved' AND review_status = 'approved'");
            $evidence->execute([$matchId]);
            if ((int) $evidence->fetchColumn() === 0) $blockers[] = 'evidencias';
        }
        return $blockers === [] ? 'completa' : 'pendente';
    }

    public function matchDetail(int $championshipId, int $matchId): ?array
    {
        $statement = $this->pdo->prepare("SELECT m.*, c.name AS championship_name, p.name AS phase_name, g.name AS group_name, r.round_number, COALESCE(NULLIF(r.round_label, ''), CONCAT('Rodada ', r.round_number)) AS rodada_label, venue.name AS venue_name, venue.city AS venue_city, ht.name AS home_team_name, at.name AS away_team_name, mo.id AS operation_id, mo.status AS operation_status, mo.review_status, mo.administrative_home_score, mo.administrative_away_score FROM matches m INNER JOIN championships c ON c.id = m.championship_id INNER JOIN competition_phases p ON p.id = m.phase_id INNER JOIN competition_groups g ON g.id = m.group_id INNER JOIN competition_rounds r ON r.id = m.round_id INNER JOIN teams ht ON ht.id = m.home_team_id INNER JOIN teams at ON at.id = m.away_team_id LEFT JOIN venues venue ON venue.id = m.venue_id LEFT JOIN match_operations mo ON mo.match_id = m.id WHERE m.id = ? AND m.championship_id = ? AND m.status = 'homologated' LIMIT 1");
        $statement->execute([$matchId, $championshipId]);
        $match = $statement->fetch();
        if (!$match) return null;
        $events = $this->pdo->prepare('SELECT e.*, t.name AS team_name, a.full_name AS athlete_name, a.sporting_name FROM match_operation_events e LEFT JOIN teams t ON t.id = e.team_id LEFT JOIN athletes a ON a.id = e.athlete_id WHERE e.match_id = ? ORDER BY e.created_at, e.id');
        $events->execute([$matchId]);
        $match['events'] = $events->fetchAll();
        $versions = $this->pdo->prepare('SELECT v.id, v.version_number, v.verification_code, v.storage_path, v.signed_storage_path, v.signed_original_name, v.signed_hash, v.created_at, v.supersedes_version_id FROM match_report_versions v INNER JOIN match_reports r ON r.id = v.match_report_id WHERE r.match_id = ? ORDER BY v.version_number DESC');
        $versions->execute([$matchId]);
        $match['versions'] = $versions->fetchAll();
        $media = $this->pdo->prepare("SELECT id, title, caption, storage_path, original_name, mime_type, captured_at, created_at FROM match_media WHERE match_id = ? AND deleted_at IS NULL AND status = 'approved' AND review_status = 'approved' ORDER BY created_at");
        $media->execute([$matchId]);
        $match['evidencias'] = $media->fetchAll();
        $eventMedia = $this->pdo->prepare("SELECT edm.id, edm.title, edm.caption, edm.storage_path, edm.original_name, edm.mime_type, edm.captured_at, edm.created_at, ed.name AS event_day_name, ed.event_date, venue.name AS venue_name, venue.city FROM event_day_media edm INNER JOIN event_days ed ON ed.id = edm.event_day_id LEFT JOIN venues venue ON venue.id = ed.venue_id WHERE edm.championship_id = ? AND ed.event_date = ? AND (ed.venue_id IS NULL OR ed.venue_id = ?) AND edm.status = 'approved' AND edm.review_status = 'approved' AND edm.deleted_at IS NULL ORDER BY edm.created_at");
        $eventMedia->execute([$championshipId, $match['match_date'], $match['venue_id'] ?? null]);
        $match['evidencias_dia_evento'] = $eventMedia->fetchAll();
        $match['document_status'] = $this->documentStatus($championshipId, $matchId);
        return $match;
    }

    public function rows(int $id, string $kind, array $filters = []): array
    {
        $sql = match ($kind) {
            'partidas' => "SELECT m.id, p.name AS fase, g.name AS grupo, ht.name AS mandante, at.name AS visitante, m.match_date AS data, m.match_time AS horario, 'Aprovada' AS situacao FROM matches m INNER JOIN competition_phases p ON p.id = m.phase_id INNER JOIN competition_groups g ON g.id = m.group_id INNER JOIN teams ht ON ht.id = m.home_team_id INNER JOIN teams at ON at.id = m.away_team_id WHERE m.championship_id = ? AND m.status = 'homologated' ORDER BY m.match_date, m.id",
            'atletas' => "SELECT a.full_name AS atleta, a.sporting_name AS nome_esportivo, t.name AS equipe, 'Aprovada' AS inscricao, ar.decided_at AS aprovado_em FROM athlete_registrations ar INNER JOIN athletes a ON a.id = ar.athlete_id INNER JOIN teams t ON t.id = ar.team_id WHERE ar.championship_id = ? AND ar.status = 'approved' ORDER BY t.name, a.full_name",
            'sumulas' => "SELECT m.id AS partida_id, ht.name AS mandante, at.name AS visitante, v.version_number AS versao, v.verification_code AS codigo, v.created_at AS gerada_em FROM match_report_versions v INNER JOIN match_reports r ON r.id = v.match_report_id INNER JOIN matches m ON m.id = r.match_id INNER JOIN teams ht ON ht.id = m.home_team_id INNER JOIN teams at ON at.id = m.away_team_id WHERE m.championship_id = ? AND m.status = 'homologated' ORDER BY v.created_at DESC",
            'evidencias' => "SELECT mm.id, m.id AS partida_id, ci.name AS item_checklist, mm.title, mm.caption, mm.visibility, mm.review_status AS situacao, mm.captured_at, mm.created_at AS enviada_em, u.name AS enviado_por, r.name AS revisado_por, mm.reviewed_at AS revisado_em, mm.rejection_reason AS justificativa, mm.file_hash FROM match_media mm INNER JOIN matches m ON m.id = mm.match_id LEFT JOIN championship_evidence_checklist_items ci ON ci.id=mm.checklist_item_id INNER JOIN users u ON u.id=mm.uploaded_by LEFT JOIN users r ON r.id=mm.reviewed_by WHERE mm.championship_id = ? AND m.status = 'homologated' AND mm.status = 'approved' AND mm.review_status = 'approved' AND mm.deleted_at IS NULL ORDER BY mm.created_at DESC",
            'atletas-documentos' => "SELECT a.full_name AS atleta, a.sporting_name AS nome_esportivo, a.birth_date AS nascimento, t.name AS equipe, 'Aprovada' AS inscricao, ar.decided_at AS aprovado_em, COALESCE(dt.name, 'Não enviado') AS tipo_documento, COALESCE(ad.status, 'Não enviado') AS situacao_documento, COALESCE(ad.original_name, '') AS arquivo, COALESCE(ad.expires_at, '') AS validade FROM athlete_registrations ar INNER JOIN athletes a ON a.id = ar.athlete_id INNER JOIN teams t ON t.id = ar.team_id LEFT JOIN athlete_documents ad ON ad.athlete_id = a.id AND ad.deleted_at IS NULL LEFT JOIN athlete_document_types dt ON dt.id = ad.document_type_id WHERE ar.championship_id = ? AND ar.status = 'approved' ORDER BY t.name, a.full_name, dt.display_order, ad.created_at DESC",
            default => [],
        };
        if ($sql === []) return [];
        $statement = $this->pdo->prepare($sql);
        $statement->execute([$id]);
        $rows = $statement->fetchAll();
        if ($kind === 'evidencias') {
            $matchFilters = array_intersect_key($filters, array_flip(['phase_id', 'group_id', 'round_id', 'team_id', 'venue_id', 'city', 'event_day_id', 'from', 'to']));
            if ($matchFilters !== []) {
                $matchIds = array_map(static fn (array $row): int => (int) $row['id'], $this->matches($id, $matchFilters));
                $allowed = array_fill_keys($matchIds, true);
                $rows = array_values(array_filter($rows, static fn (array $row): bool => isset($allowed[(int) $row['partida_id']])));
            }
            $where = ['edm.championship_id = ?', "edm.status = 'approved'", "edm.review_status = 'approved'", 'edm.deleted_at IS NULL', 'ed.deleted_at IS NULL'];
            $params = [$id];
            if (!empty($filters['event_day_id']) && ctype_digit((string) $filters['event_day_id'])) { $where[] = 'ed.id = ?'; $params[] = (int) $filters['event_day_id']; }
            if (!empty($filters['venue_id']) && ctype_digit((string) $filters['venue_id'])) { $where[] = 'ed.venue_id = ?'; $params[] = (int) $filters['venue_id']; }
            if (!empty($filters['city'])) { $where[] = 'venue.city = ?'; $params[] = (string) $filters['city']; }
            if (!empty($filters['from'])) { $where[] = 'ed.event_date >= ?'; $params[] = substr((string) $filters['from'], 0, 10); }
            if (!empty($filters['to'])) { $where[] = 'ed.event_date <= ?'; $params[] = substr((string) $filters['to'], 0, 10); }
            $extra = $this->pdo->prepare("SELECT edm.id, NULL AS partida_id, ci.name AS item_checklist, edm.title, edm.caption, edm.visibility, edm.review_status AS situacao, edm.captured_at, edm.created_at AS enviada_em, u.name AS enviado_por, r.name AS revisado_por, edm.reviewed_at AS revisado_em, edm.rejection_reason AS justificativa, edm.file_hash, ed.name AS dia_evento, ed.event_date AS data_evento, venue.name AS local, venue.city AS cidade FROM event_day_media edm INNER JOIN event_days ed ON ed.id = edm.event_day_id LEFT JOIN venues venue ON venue.id = ed.venue_id LEFT JOIN championship_evidence_checklist_items ci ON ci.id=edm.checklist_item_id INNER JOIN users u ON u.id=edm.uploaded_by LEFT JOIN users r ON r.id=edm.reviewed_by WHERE " . implode(' AND ', $where) . ' ORDER BY edm.created_at DESC');
            $extra->execute($params);
            $rows = array_merge($rows, $extra->fetchAll());
        }
        return $rows;
    }

    public function athleteDocumentFiles(int $championshipId): array
    {
        $statement = $this->pdo->prepare("SELECT ad.id, ad.storage_path AS path, ad.original_name AS name, a.id AS athlete_id, a.full_name AS athlete_name, dt.name AS document_type FROM athlete_registrations ar INNER JOIN athletes a ON a.id = ar.athlete_id INNER JOIN athlete_documents ad ON ad.athlete_id = a.id AND ad.deleted_at IS NULL AND ad.status = 'approved' INNER JOIN athlete_document_types dt ON dt.id = ad.document_type_id WHERE ar.championship_id = ? AND ar.status = 'approved' ORDER BY a.full_name, ad.id");
        $statement->execute([$championshipId]);
        return $statement->fetchAll();
    }

    public function exportRows(int $id, array $filters = []): array
    {
        $rows = $this->matches($id, $filters);
        return array_map(static fn (array $row): array => [
            'partida' => (int) $row['id'], 'fase' => $row['fase'], 'grupo' => $row['grupo'], 'rodada' => $row['rodada_label'] ?? ('Rodada ' . (int) $row['round_number']),
            'mandante' => $row['mandante'], 'visitante' => $row['visitante'], 'data' => $row['match_date'], 'horario' => $row['match_time'],
            'local' => $row['venue_name'] ?? '', 'cidade' => $row['venue_city'] ?? '',
            'publicacao' => $row['publication_status'] ?: 'interna', 'documentacao' => $row['document_status'],
        ], $rows);
    }

    public function packageFiles(int $championshipId, array $filters = []): array
    {
        $matches = $this->matches($championshipId, $filters);
        $files = [];
        foreach ($matches as $match) {
            if (!empty($match['sumula_path'])) $files[] = ['path' => $match['sumula_path'], 'name' => 'sumulas/partida-' . (int) $match['id'] . '.pdf'];
            if (!empty($match['signed_storage_path'])) $files[] = ['path' => $match['signed_storage_path'], 'name' => 'sumulas/partida-' . (int) $match['id'] . '-assinada.pdf'];
            $media = $this->pdo->prepare("SELECT storage_path, original_name FROM match_media WHERE match_id = ? AND deleted_at IS NULL AND status = 'approved' AND review_status = 'approved'");
            $media->execute([(int) $match['id']]);
            foreach ($media->fetchAll() as $item) $files[] = ['path' => $item['storage_path'], 'name' => 'evidencias/partida-' . (int) $match['id'] . '-' . basename((string) $item['original_name'])];
        }
        $eventWhere = ['ed.championship_id = ?', 'ed.deleted_at IS NULL', 'ed.status = \'active\'']; $eventParams = [$championshipId];
        if (!empty($filters['event_day_id'])) { $eventWhere[] = 'ed.id = ?'; $eventParams[] = (int) $filters['event_day_id']; }
        if (!empty($filters['venue_id'])) { $eventWhere[] = 'ed.venue_id = ?'; $eventParams[] = (int) $filters['venue_id']; }
        if (!empty($filters['city'])) { $eventWhere[] = 'venue.city = ?'; $eventParams[] = (string) $filters['city']; }
        if (!empty($filters['from'])) { $eventWhere[] = 'ed.event_date >= ?'; $eventParams[] = substr((string) $filters['from'], 0, 10); }
        if (!empty($filters['to'])) { $eventWhere[] = 'ed.event_date <= ?'; $eventParams[] = substr((string) $filters['to'], 0, 10); }
        $eventStatement = $this->pdo->prepare("SELECT edm.storage_path, edm.original_name, edm.id, ed.id AS event_day_id FROM event_day_media edm INNER JOIN event_days ed ON ed.id = edm.event_day_id LEFT JOIN venues venue ON venue.id = ed.venue_id WHERE " . implode(' AND ', $eventWhere) . " AND edm.status = 'approved' AND edm.review_status = 'approved' AND edm.deleted_at IS NULL ORDER BY edm.id");
        $eventStatement->execute($eventParams);
        foreach ($eventStatement->fetchAll() as $item) $files[] = ['path' => $item['storage_path'], 'name' => 'evidencias/dia-evento-' . (int) $item['event_day_id'] . '-' . (int) $item['id'] . '-' . basename((string) $item['original_name'])];
        return ['matches' => $matches, 'files' => $files];
    }

    public function attachSignedReport(int $championshipId, int $matchId, string $path, string $original, string $mime, int $size, string $hash, int $userId): bool
    {
        $statement = $this->pdo->prepare('UPDATE match_report_versions v INNER JOIN match_reports r ON r.current_version_id = v.id SET v.signed_storage_path = ?, v.signed_original_name = ?, v.signed_mime_type = ?, v.signed_file_size = ?, v.signed_hash = ?, v.signed_uploaded_by = ?, v.signed_uploaded_at = ? WHERE r.match_id = ? AND r.championship_id = ?');
        $statement->execute([$path, $original, $mime, $size, $hash, $userId, date('Y-m-d H:i:s'), $matchId, $championshipId]);
        return $statement->rowCount() > 0;
    }

    public function log(int $championshipId, int $userId, string $kind, int $count, string $format = 'csv', array $filters = [], array $matchIds = [], ?string $fileName = null, ?string $fileHash = null): void
    {
        $statement = $this->pdo->prepare('INSERT INTO accountability_export_logs (championship_id, user_id, export_kind, format, file_count, filters_json, match_ids, file_name, file_hash, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $statement->execute([$championshipId, $userId, $kind, $format, $count, json_encode($filters, JSON_UNESCAPED_UNICODE), json_encode(array_values($matchIds)), $fileName, $fileHash, date('Y-m-d H:i:s')]);
    }
}
