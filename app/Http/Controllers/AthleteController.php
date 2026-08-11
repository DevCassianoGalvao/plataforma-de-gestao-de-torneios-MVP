<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\AthleteDocumentRepository;
use App\Repositories\AthleteDocumentTypeRepository;
use App\Repositories\AthleteRepository;
use App\Repositories\GuardianRepository;
use App\Repositories\PositionRepository;
use App\Services\AthleteAccessService;
use App\Services\AthleteRules;
use App\Services\AuditService;
use App\Services\RegistrationService;
use App\Services\StorageService;
use App\Services\UploadRules;

final class AthleteController extends Controller
{
    public function __construct(
        $users,
        \App\Services\AuthorizationService $authorization,
        AuditService $audit,
        private readonly AthleteRepository $athletes,
        private readonly PositionRepository $positions,
        private readonly GuardianRepository $guardians,
        private readonly AthleteDocumentTypeRepository $documentTypes,
        private readonly AthleteDocumentRepository $documents,
        private readonly AthleteAccessService $access,
        private readonly StorageService $storage,
        private readonly RegistrationService $registrations,
    ) {
        parent::__construct($users, $authorization, $audit);
    }

    public function index(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'athletes.view');
        if ($guard instanceof Response) return $guard;
        return $this->page('Atletas', 'admin/athletes/index', [
            'user' => $guard,
            'items' => $this->access->list($guard, $request->query),
            'teams' => $this->access->authorizedTeams($guard),
            'positions' => $this->positions->list(),
            'query' => $request->query,
            'canCreate' => $this->canCreate($guard),
            'message' => Session::consumeFlash('athlete_message'),
        ]);
    }

    public function positions(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'positions.view');
        if ($guard instanceof Response) return $guard;
        return $this->page('Posicoes', 'admin/athletes/positions', ['user' => $guard, 'items' => $this->positions->list(true)]);
    }

    public function createForm(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request);
        if ($guard instanceof Response || !$this->canCreate($guard)) return $guard instanceof Response ? $guard : Response::forbidden();
        return $this->formPage('Novo atleta', $guard, $this->blank(), false, []);
    }

    public function create(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request);
        if ($guard instanceof Response || !$this->canCreate($guard)) return $guard instanceof Response ? $guard : Response::forbidden();
        $data = $this->athleteData($request, 'draft');
        $guardianData = $this->guardianData($request);
        if (!$this->validCsrf($request)) return $this->formError($guard, $data, $guardianData, ['A sessao expirou.'], false, 419);
        $team = $this->access->team($guard, (int) $data['team_id'], true);
        $data['requires_guardian'] = (int) ($team['requires_guardian'] ?? 0);
        $errors = $team ? $this->validateAthlete($data, $team, null) : ['Escolha uma equipe autorizada.'];
        $errors = array_merge($errors, $this->validatePositions($data));
        $minor = $this->isMinor($data);
        $needsGuardian = (bool) $data['requires_guardian'] && $minor;
        if ($needsGuardian) $errors = array_merge($errors, $this->validateGuardianIfNeeded($guardianData));
        $stored = null;
        $identityStored = null;
        $identityType = $this->documentTypes->findByKey('athlete_document');
        if (!$identityType) $errors[] = 'Tipo de documento do atleta indisponivel.';
        if (($request->files['photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Envie uma foto do atleta.';
        } else {
            try {
                $stored = $this->storage->storeOptimizedImage($request->files['photo'], 'athletes/' . $data['team_id'], ['max_width' => 1400, 'max_height' => 1400]);
                $data['photo_path'] = $stored['path'];
            } catch (\Throwable $exception) {
                $errors[] = $exception->getMessage();
            }
        }
        if (($request->files['identity_document']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Envie a foto ou o arquivo do documento do atleta.';
        } else {
            $identityStored = $this->storeIdentityDocument($request->files['identity_document'], 'team-' . $data['team_id'], $errors);
        }
        if ($errors) {
            if ($stored) $this->storage->delete($stored['path']);
            if ($identityStored) $this->storage->delete($identityStored['path']);
            return $this->formError($guard, $data, $guardianData, $errors, false, 422);
        }
        $id = 0;
        $identityDocumentId = 0;
        try {
            $id = $this->athletes->create($data, (int) $guard['id']);
            $this->athletes->syncSecondaryPositions($id, $data['secondary_position_ids']);
            if ($needsGuardian) $this->createGuardianLink($id, $guardianData);
            $identityDocumentId = $this->documents->create(['athlete_id' => $id, 'guardian_id' => 0, 'document_type_id' => (int) $identityType['id'], 'storage_path' => $identityStored['path'], 'original_name' => $identityStored['original_name'], 'mime_type' => $identityStored['mime'], 'size_bytes' => $identityStored['size'], 'expires_at' => '', 'observation' => 'Documento enviado no cadastro do atleta.'], (int) $guard['id']);
        } catch (\Throwable $exception) {
            if ($stored) $this->storage->delete($stored['path']);
            if ($identityStored) $this->storage->delete($identityStored['path']);
            if ($identityDocumentId) $this->documents->softDelete($identityDocumentId);
            if ($id) $this->athletes->softDelete($id);
            return $this->formError($guard, $data, $guardianData, ['Nao foi possivel salvar o atleta.'], false, 422);
        }
        $this->audit->record('athletes.created', (int) $guard['id'], 'athlete', $id, ['team_id' => (int) $data['team_id']], $request);
        if ((string) ($request->body['registration_action'] ?? '') === 'create') {
            $registration = $this->registrations->createDraft(
                (int) $guard['id'],
                (int) $team['championship_id'],
                (int) $data['team_id'],
                $id,
                null,
                '',
                $request,
            );
            if ($registration['ok']) {
                Session::flash('registration_message', 'Atleta cadastrado e inscrição criada. Revise os dados e envie para análise.');
                return Response::redirect(Config::url('/admin/inscricoes/' . $registration['id']));
            }
            Session::flash('athlete_message', 'Atleta cadastrado, mas não foi possível criar a inscrição: ' . implode(' ', $registration['errors']));
        } else {
            Session::flash('athlete_message', 'Atleta cadastrado.');
        }
        return Response::redirect(Config::url('/admin/atletas/' . $id));
    }

    public function show(Request $request, array $params = []): Response
    {
        [$guard, $athlete] = $this->context($request, (int) ($params[0] ?? 0), 'athletes.view');
        if ($guard instanceof Response) return $guard;
        return $this->page((string) $athlete['full_name'], 'admin/athletes/show', [
            'user' => $guard,
            'athlete' => $athlete,
            'secondary' => $this->athletes->secondaryPositions((int) $athlete['id']),
            'guardians' => $this->guardians->listForAthlete((int) $athlete['id']),
            'documents' => $this->documents->listForAthlete((int) $athlete['id']),
            'canEdit' => $this->canMutation($guard, (int) $athlete['id'], 'athletes.update', 'athletes.manage_own'),
            'canStatus' => $this->canStatus($guard, (int) $athlete['id']),
            'canGuardians' => $this->canGuardianMutation($guard, (int) $athlete['id']),
            'canDocuments' => $this->canDocumentMutation($guard, (int) $athlete['id']),
            'canReviewDocuments' => $this->canReview($guard, (int) $athlete['id']),
            'canCreateRegistration' => $this->canCreateRegistration($guard, (int) $athlete['team_id']),
            'message' => Session::consumeFlash('athlete_message'),
        ]);
    }

    public function editForm(Request $request, array $params = []): Response
    {
        [$guard, $athlete] = $this->mutationContext($request, (int) ($params[0] ?? 0));
        if ($guard instanceof Response) return $guard;
        $athlete['secondary_position_ids'] = array_map('intval', array_column($this->athletes->secondaryPositions((int) $athlete['id']), 'id'));
        return $this->formPage('Editar atleta', $guard, $athlete, true, []);
    }

    public function update(Request $request, array $params = []): Response
    {
        [$guard, $athlete] = $this->mutationContext($request, (int) ($params[0] ?? 0));
        if ($guard instanceof Response) return $guard;
        $data = $this->athleteData($request, (string) $athlete['status']);
        $data['team_id'] = (int) $athlete['team_id'];
        $data['photo_path'] = $athlete['photo_path'];
        $data['preferred_number'] = $athlete['preferred_number'] ?? null;
        $data['dominant_foot'] = $athlete['dominant_foot'] ?? null;
        $data['secondary_position_ids'] = array_map('intval', array_column($this->athletes->secondaryPositions((int) $athlete['id']), 'id'));
        $data['requires_guardian'] = (int) ($athlete['requires_guardian'] ?? 0);
        $guardianData = $this->guardianData($request);
        if (!$this->validCsrf($request)) return $this->formError($guard, array_merge($athlete, $data), $guardianData, ['A sessao expirou.'], true, 419);
        $team = $this->access->team($guard, (int) $athlete['team_id'], true);
        $errors = $team ? $this->validateAthlete($data, $team, (int) $athlete['id']) : ['Equipe fora do escopo autorizado.'];
        $errors = array_merge($errors, $this->validatePositions($data));
        $minor = $this->isMinor($data);
        $needsGuardian = (bool) $data['requires_guardian'] && $minor;
        $profileChanged = $this->profileChanged($data, $athlete, $this->athletes->secondaryPositions((int) $athlete['id']));
        if ($needsGuardian && $profileChanged && !$this->guardians->hasActiveForAthlete((int) $athlete['id']) && !$this->hasGuardianInput($guardianData)) $errors[] = 'Atletas menores precisam de responsavel legal.';
        $stored = null;
        if (($request->files['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $stored = $this->storage->storeOptimizedImage($request->files['photo'], 'athletes/' . $data['team_id'], ['max_width' => 1400, 'max_height' => 1400]);
                $data['photo_path'] = $stored['path'];
            } catch (\Throwable $exception) {
                $errors[] = $exception->getMessage();
            }
        }
        $identityStored = null;
        $identityType = $this->documentTypes->findByKey('athlete_document');
        $identityDocumentId = 0;
        if (($request->files['identity_document']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if (!$identityType) $errors[] = 'Tipo de documento do atleta indisponivel.';
            else $identityStored = $this->storeIdentityDocument($request->files['identity_document'], 'athlete-' . $athlete['id'], $errors);
        }
        if ($this->athletes->duplicateExists((int) $data['team_id'], $data['full_name'], $data['birth_date'], (int) $athlete['id'])) $errors[] = 'Ja existe atleta com este nome e data de nascimento nesta equipe.';
        if ($errors) {
            if ($stored) $this->storage->delete($stored['path']);
            if ($identityStored) $this->storage->delete($identityStored['path']);
            return $this->formError($guard, array_merge($athlete, $data), $guardianData, $errors, true, 422);
        }
        try {
            if ($identityStored) $identityDocumentId = $this->documents->create(['athlete_id' => (int) $athlete['id'], 'guardian_id' => 0, 'document_type_id' => (int) $identityType['id'], 'storage_path' => $identityStored['path'], 'original_name' => $identityStored['original_name'], 'mime_type' => $identityStored['mime'], 'size_bytes' => $identityStored['size'], 'expires_at' => '', 'observation' => 'Documento atualizado pelo cadastro do atleta.'], (int) $guard['id']);
            $this->athletes->update((int) $athlete['id'], $data);
            $this->athletes->syncSecondaryPositions((int) $athlete['id'], $data['secondary_position_ids']);
            if ($needsGuardian && $this->hasGuardianInput($guardianData)) $this->createGuardianLink((int) $athlete['id'], $guardianData);
        } catch (\Throwable) {
            if ($stored) $this->storage->delete($stored['path']);
            if ($identityStored) $this->storage->delete($identityStored['path']);
            if ($identityDocumentId) $this->documents->softDelete($identityDocumentId);
            return $this->formError($guard, array_merge($athlete, $data), $guardianData, ['Nao foi possivel atualizar o atleta.'], true, 422);
        }
        if ($stored && $athlete['photo_path']) $this->storage->delete((string) $athlete['photo_path']);
        $this->audit->record('athletes.updated', (int) $guard['id'], 'athlete', (int) $athlete['id'], [], $request);
        Session::flash('athlete_message', 'Atleta atualizado.');
        return Response::redirect(Config::url('/admin/atletas/' . $athlete['id']));
    }

    public function status(Request $request, array $params = []): Response
    {
        [$guard, $athlete] = $this->context($request, (int) ($params[0] ?? 0), 'athletes.view');
        if ($guard instanceof Response) return $guard;
        if (!$this->canStatus($guard, (int) $athlete['id'])) return Response::forbidden();
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $next = (string) ($request->body['status'] ?? '');
        if (!AthleteRules::transition((string) $athlete['status'], $next)) return $this->errorPage('Status do atleta', 'errors/simple', ['message' => 'Transicao de status invalida.'], 422);
        $this->athletes->updateStatus((int) $athlete['id'], $next);
        $this->audit->record('athletes.status_changed', (int) $guard['id'], 'athlete', (int) $athlete['id'], ['previous' => $athlete['status'], 'next' => $next, 'reason' => trim((string) ($request->body['reason'] ?? ''))], $request);
        Session::flash('athlete_message', 'Status do atleta atualizado.');
        return Response::redirect(Config::url('/admin/atletas/' . $athlete['id']));
    }

    public function delete(Request $request, array $params = []): Response
    {
        [$guard, $athlete] = $this->context($request, (int) ($params[0] ?? 0), 'athletes.deactivate', true);
        if ($guard instanceof Response) return $guard;
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $this->athletes->softDelete((int) $athlete['id']);
        $this->audit->record('athletes.deleted', (int) $guard['id'], 'athlete', (int) $athlete['id'], [], $request);
        Session::flash('athlete_message', 'Atleta arquivado.');
        return Response::redirect(Config::url('/admin/atletas'));
    }

    public function guardians(Request $request, array $params = []): Response
    {
        [$guard, $athlete] = $this->context($request, (int) ($params[0] ?? 0), 'athlete_guardians.view');
        if ($guard instanceof Response) return $guard;
        return $this->page('Responsaveis legais', 'admin/athletes/guardians', ['user' => $guard, 'athlete' => $athlete, 'items' => $this->guardians->listForAthlete((int) $athlete['id']), 'canManage' => $this->canGuardianMutation($guard, (int) $athlete['id']), 'errors' => []]);
    }

    public function saveGuardian(Request $request, array $params = []): Response
    {
        [$guard, $athlete] = $this->context($request, (int) ($params[0] ?? 0), 'athlete_guardians.view');
        if ($guard instanceof Response) return $guard;
        if (!$this->canGuardianMutation($guard, (int) $athlete['id'])) return Response::forbidden();
        $data = $this->guardianData($request);
        if (!$this->validCsrf($request)) return $this->guardianError($guard, $athlete, ['A sessao expirou.'], $data);
        $errors = AthleteRules::validateGuardian($data);
        if ($errors) return $this->guardianError($guard, $athlete, $errors, $data);
        $guardianId = $this->guardians->create($data);
        $this->guardians->link((int) $athlete['id'], $guardianId, $data);
        $this->audit->record('athlete_guardians.created', (int) $guard['id'], 'athlete_guardian', $guardianId, ['athlete_id' => (int) $athlete['id']], $request);
        Session::flash('athlete_message', 'Responsavel legal vinculado.');
        return Response::redirect(Config::url('/admin/atletas/' . $athlete['id'] . '/responsaveis'));
    }

    public function documents(Request $request, array $params = []): Response
    {
        [$guard, $athlete] = $this->context($request, (int) ($params[0] ?? 0), 'athlete_documents.view');
        if ($guard instanceof Response) return $guard;
        return $this->page('Documentos do atleta', 'admin/athletes/documents', ['user' => $guard, 'athlete' => $athlete, 'items' => $this->documents->listForAthlete((int) $athlete['id']), 'types' => $this->documentTypes->list(), 'guardians' => $this->guardians->listForAthlete((int) $athlete['id']), 'canManage' => $this->canDocumentMutation($guard, (int) $athlete['id']), 'canReview' => $this->canReview($guard, (int) $athlete['id']), 'canCreateRegistration' => $this->canCreateRegistration($guard, (int) $athlete['team_id']), 'errors' => []]);
    }

    public function saveDocument(Request $request, array $params = []): Response
    {
        [$guard, $athlete] = $this->context($request, (int) ($params[0] ?? 0), 'athlete_documents.view');
        if ($guard instanceof Response) return $guard;
        if (!$this->canDocumentMutation($guard, (int) $athlete['id'])) return Response::forbidden();
        if (!$this->validCsrf($request)) return $this->documentError($guard, $athlete, ['A sessao expirou.'], [], 419);
        $typeId = (int) ($request->body['document_type_id'] ?? 0);
        $guardianId = (int) ($request->body['guardian_id'] ?? 0);
        $type = $this->documentTypes->find($typeId);
        $errors = $type ? [] : ['Escolha um tipo de documento valido.'];
        if ($guardianId && !$this->guardians->linkedToAthlete((int) $athlete['id'], $guardianId)) $errors[] = 'Responsavel invalido para este atleta.';
        if ($type && (int) $type['guardian_applicable'] === 1 && !$guardianId) $errors[] = 'Este tipo de documento exige responsavel legal.';
        $expiresAt = trim((string) ($request->body['expires_at'] ?? ''));
        if ($expiresAt !== '' && !\DateTimeImmutable::createFromFormat('!Y-m-d', $expiresAt)) $errors[] = 'Validade do documento invalida.';
        $file = $request->files['document'] ?? [];
        $stored = null;
        try {
            UploadRules::validate($file, ['application/pdf' => ['pdf'], 'image/png' => ['png'], 'image/jpeg' => ['jpg', 'jpeg'], 'image/webp' => ['webp']], 10485760);
            $stored = $this->storage->store($file, 'athlete-documents/' . $athlete['id'], ['application/pdf', 'image/png', 'image/jpeg', 'image/webp'], 10485760);
        } catch (\Throwable $exception) {
            $errors[] = $exception->getMessage();
        }
        if ($errors || !$stored) return $this->documentError($guard, $athlete, $errors ?: ['Arquivo obrigatorio.'], $request->body);
        $data = ['athlete_id' => (int) $athlete['id'], 'guardian_id' => $guardianId, 'document_type_id' => $typeId, 'storage_path' => $stored['path'], 'original_name' => $stored['original_name'], 'mime_type' => $stored['mime'], 'size_bytes' => $stored['size'], 'expires_at' => $expiresAt, 'observation' => trim((string) ($request->body['observation'] ?? ''))];
        try {
            $id = $this->documents->create($data, (int) $guard['id']);
        } catch (\Throwable) {
            $this->storage->delete($stored['path']);
            return $this->documentError($guard, $athlete, ['Nao foi possivel salvar o documento.'], $request->body);
        }
        $this->audit->record('athlete_documents.created', (int) $guard['id'], 'athlete_document', $id, ['athlete_id' => (int) $athlete['id'], 'document_type_id' => $typeId], $request);
        Session::flash('athlete_message', 'Documento enviado para analise.');
        return Response::redirect(Config::url('/admin/atletas/' . $athlete['id'] . '/documentos'));
    }

    public function documentAsset(Request $request, array $params = []): Response
    {
        [$guard, $athlete] = $this->context($request, (int) ($params[0] ?? 0), 'athlete_documents.view');
        if ($guard instanceof Response) return $guard;
        if ((string) ($params[1] ?? '') === 'photo') {
            if (!$athlete['photo_path']) return Response::html('Arquivo nao encontrado.', 404);
            $file = $this->storage->read((string) $athlete['photo_path']);
            if (!$file) return Response::html('Arquivo nao encontrado.', 404);
            return Response::binary($file['body'], $file['mime'], $file['name']);
        }
        $document = $this->documents->findForAthlete((int) ($params[1] ?? 0), (int) $athlete['id']);
        if (!$document) return Response::html('Arquivo nao encontrado.', 404);
        $file = $this->storage->read((string) $document['storage_path']);
        if (!$file) return Response::html('Arquivo nao encontrado.', 404);
        return Response::binary($file['body'], $file['mime'], $file['name']);
    }

    public function reviewDocument(Request $request, array $params = []): Response
    {
        [$guard, $athlete] = $this->context($request, (int) ($params[0] ?? 0), 'athlete_documents.view');
        if ($guard instanceof Response) return $guard;
        if (!$this->canReview($guard, (int) $athlete['id'])) return Response::forbidden();
        $document = $this->documents->findForAthlete((int) ($params[1] ?? 0), (int) $athlete['id']);
        if (!$document) return Response::html('Documento nao encontrado.', 404);
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $status = (string) ($request->body['status'] ?? '');
        $reason = trim((string) ($request->body['rejection_reason'] ?? ''));
        if (!AthleteRules::validateDocumentStatus($status) || ($status === 'rejected' && $reason === '')) return $this->errorPage('Documento', 'errors/simple', ['message' => 'Status ou motivo de rejeicao invalido.'], 422);
        $this->documents->review((int) $document['id'], $status, $reason, (int) $guard['id']);
        $this->audit->record('athlete_documents.reviewed', (int) $guard['id'], 'athlete_document', (int) $document['id'], ['athlete_id' => (int) $athlete['id'], 'status' => $status], $request);
        return Response::redirect(Config::url('/admin/atletas/' . $athlete['id'] . '/documentos'));
    }

    private function context(Request $request, int $id, string $permission, bool $mutation = false): array
    {
        $guard = $this->guard($request, $permission);
        if ($guard instanceof Response) return [$guard, null];
        $athlete = $this->access->find($guard, $id, $permission, $mutation);
        if (!$athlete) return [Response::forbidden(), null];
        return [$guard, $athlete];
    }

    private function mutationContext(Request $request, int $id): array
    {
        $guard = $this->guard($request);
        if ($guard instanceof Response) return [$guard, null];
        $permission = $this->access->scope($guard) === 'team' ? 'athletes.manage_own' : 'athletes.update';
        $athlete = $this->access->find($guard, $id, $permission, true);
        if (!$athlete) return [Response::forbidden(), null];
        return [$guard, $athlete];
    }

    private function canCreate(array $user): bool
    {
        $permission = $this->access->scope($user) === 'team' ? 'athletes.manage_own' : 'athletes.create';
        return $this->authorization->can($user, $permission);
    }

    private function canMutation(array $user, int $id, string $global, string $own): bool
    {
        $permission = $this->access->scope($user) === 'team' ? $own : $global;
        return $this->access->find($user, $id, $permission, true) !== null;
    }

    private function canStatus(array $user, int $id): bool
    {
        return $this->canMutation($user, $id, 'athletes.deactivate', 'athletes.manage_own');
    }

    private function canGuardianMutation(array $user, int $id): bool
    {
        return $this->canMutation($user, $id, 'athlete_guardians.update', 'athlete_guardians.manage_own');
    }

    private function canDocumentMutation(array $user, int $id): bool
    {
        return $this->canMutation($user, $id, 'athlete_documents.create', 'athlete_documents.manage_own');
    }

    private function canReview(array $user, int $id): bool
    {
        return $this->authorization->can($user, 'athlete_documents.review') && $this->access->find($user, $id, 'athlete_documents.review') !== null;
    }

    private function validateAthlete(array $data, array $team, ?int $exceptId): array
    {
        $errors = AthleteRules::validate($data, $team);
        if ($exceptId === null && $this->athletes->duplicateExists((int) $data['team_id'], $data['full_name'], $data['birth_date'])) $errors[] = 'Ja existe atleta com este nome e data de nascimento nesta equipe.';
        return $errors;
    }

    private function validatePositions(array $data): array
    {
        $errors = [];
        $primary = $this->positions->find((int) $data['primary_position_id']);
        if (!$primary) $errors[] = 'Posicao principal invalida.';
        $secondary = $this->positions->ids($data['secondary_position_ids']);
        if (count($secondary) !== count(array_unique(array_map('intval', $data['secondary_position_ids'])))) $errors[] = 'Existe uma posicao secundaria invalida.';
        if (in_array((int) $data['primary_position_id'], $secondary, true)) $errors[] = 'A posicao principal nao pode repetir entre as secundarias.';
        return $errors;
    }

    private function validateGuardianIfNeeded(array $data): array
    {
        return $this->hasGuardianInput($data) ? AthleteRules::validateGuardian($data) : ['Atletas menores precisam de responsavel legal.'];
    }

    private function isMinor(array $data): bool
    {
        try { return AthleteRules::isMinor((string) $data['birth_date']); } catch (\Throwable) { return false; }
    }

    private function canCreateRegistration(array $user, int $teamId): bool
    {
        if (!$this->authorization->can($user, 'registrations.create')) return false;
        return $this->access->team($user, $teamId, true) !== null;
    }

    private function storeIdentityDocument(array $file, string $directory, array &$errors): ?array
    {
        try {
            UploadRules::validate($file, ['application/pdf' => ['pdf'], 'image/png' => ['png'], 'image/jpeg' => ['jpg', 'jpeg'], 'image/webp' => ['webp']], 10485760);
            return $this->storage->store($file, 'athlete-documents/' . $directory, ['application/pdf', 'image/png', 'image/jpeg', 'image/webp'], 10485760);
        } catch (\Throwable $exception) {
            $errors[] = $exception->getMessage();
            return null;
        }
    }

    private function createGuardianLink(int $athleteId, array $data): void
    {
        $guardianId = $this->guardians->create($data);
        $this->guardians->link($athleteId, $guardianId, $data);
    }

    private function hasGuardianInput(array $data): bool
    {
        return trim((string) ($data['full_name'] ?? '')) !== '' || trim((string) ($data['document_number'] ?? '')) !== '';
    }

    private function profileChanged(array $data, array $athlete, array $secondaryPositions): bool
    {
        foreach (['full_name', 'sporting_name', 'birth_date', 'gender', 'status', 'private_notes'] as $field) {
            if ((string) ($data[$field] ?? '') !== (string) ($athlete[$field] ?? '')) return true;
        }
        if ((int) ($data['primary_position_id'] ?? 0) !== (int) ($athlete['primary_position_id'] ?? 0)) return true;
        $current = array_map(static fn (array $position): int => (int) $position['id'], $secondaryPositions);
        $submitted = array_map('intval', $data['secondary_position_ids'] ?? []);
        sort($current);
        sort($submitted);
        return $current !== $submitted;
    }

    private function athleteData(Request $request, string $status): array
    {
        $secondary = $request->body['secondary_position_ids'] ?? [];
        if (!is_array($secondary)) $secondary = [$secondary];
        return ['team_id' => (int) ($request->body['team_id'] ?? 0), 'full_name' => trim((string) ($request->body['full_name'] ?? '')), 'sporting_name' => trim((string) ($request->body['sporting_name'] ?? '')), 'photo_path' => null, 'birth_date' => trim((string) ($request->body['birth_date'] ?? '')), 'gender' => trim((string) ($request->body['gender'] ?? '')), 'primary_position_id' => (int) ($request->body['primary_position_id'] ?? 0), 'secondary_position_ids' => array_values(array_filter(array_map('intval', $secondary))), 'preferred_number' => null, 'dominant_foot' => '', 'status' => $status, 'private_notes' => trim((string) ($request->body['private_notes'] ?? ''))];
    }

    private function guardianData(Request $request): array
    {
        return ['full_name' => trim((string) ($request->body['guardian_full_name'] ?? '')), 'relationship' => trim((string) ($request->body['guardian_relationship'] ?? '')), 'phone' => trim((string) ($request->body['guardian_phone'] ?? '')), 'email' => trim((string) ($request->body['guardian_email'] ?? '')), 'document_number' => trim((string) ($request->body['guardian_document'] ?? '')), 'status' => 'active', 'authorization_status' => 'pending', 'authorization_note' => trim((string) ($request->body['guardian_authorization_note'] ?? '')), 'is_primary' => true];
    }

    private function blank(): array
    {
        return ['team_id' => '', 'full_name' => '', 'sporting_name' => '', 'photo_path' => '', 'birth_date' => '', 'gender' => '', 'primary_position_id' => '', 'secondary_position_ids' => [], 'preferred_number' => '', 'dominant_foot' => '', 'requires_guardian' => 0, 'status' => 'draft', 'private_notes' => ''];
    }

    private function formPage(string $title, array $user, array $record, bool $editing, array $errors): Response
    {
        $guardian = ['full_name' => '', 'relationship' => '', 'phone' => '', 'email' => '', 'document_number' => '', 'authorization_note' => ''];
        return $this->page($title, 'admin/athletes/form', ['user' => $user, 'record' => $record, 'teams' => $editing ? [] : $this->access->authorizedTeams($user), 'positions' => $this->positions->list(), 'guardian' => $guardian, 'errors' => $errors, 'editing' => $editing, 'canCreateRegistration' => !$editing && $this->authorization->can($user, 'registrations.create')]);
    }

    private function formError(array $user, array $record, array $guardian, array $errors, bool $editing, int $status): Response
    {
        return $this->errorPage($editing ? 'Editar atleta' : 'Novo atleta', 'admin/athletes/form', ['user' => $user, 'record' => $record, 'teams' => $editing ? [] : $this->access->authorizedTeams($user), 'positions' => $this->positions->list(), 'guardian' => $guardian, 'errors' => $errors, 'editing' => $editing, 'canCreateRegistration' => !$editing && $this->authorization->can($user, 'registrations.create')], $status);
    }

    private function guardianError(array $user, array $athlete, array $errors, array $guardian): Response
    {
        return $this->errorPage('Responsaveis legais', 'admin/athletes/guardians', ['user' => $user, 'athlete' => $athlete, 'items' => $this->guardians->listForAthlete((int) $athlete['id']), 'canManage' => true, 'errors' => $errors, 'guardian' => $guardian], 422);
    }

    private function documentError(array $user, array $athlete, array $errors, array $record, int $status = 422): Response
    {
        return $this->errorPage('Documentos do atleta', 'admin/athletes/documents', ['user' => $user, 'athlete' => $athlete, 'items' => $this->documents->listForAthlete((int) $athlete['id']), 'types' => $this->documentTypes->list(), 'guardians' => $this->guardians->listForAthlete((int) $athlete['id']), 'canManage' => true, 'canReview' => $this->authorization->can($user, 'athlete_documents.review'), 'errors' => $errors, 'record' => $record], $status);
    }
}
