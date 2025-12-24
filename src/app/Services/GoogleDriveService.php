<?php

namespace App\Services;

use App\Models\Client;
use App\Models\GoogleDriveActivityLog;
use App\Models\GoogleDriveFile;
use App\Models\GoogleDriveFolder;
use App\Models\GoogleDriveSetting;
use App\Models\Process;
use Google\Client as GoogleClient;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GoogleDriveService
{
    protected ?GoogleClient $client = null;
    protected ?Drive $driveService = null;
    protected ?GoogleDriveSetting $settings = null;

    /**
     * Inicializa o serviço com as configurações do usuário
     */
    public function __construct(?GoogleDriveSetting $settings = null)
    {
        $this->settings = $settings ?? GoogleDriveSetting::getForCurrentUser();
    }

    // ==========================================
    // CONFIGURAÇÃO DO CLIENTE GOOGLE
    // ==========================================

    /**
     * Obtém o cliente Google configurado
     */
    public function getClient(): GoogleClient
    {
        if ($this->client) {
            return $this->client;
        }

        $this->client = new GoogleClient();
        $this->client->setApplicationName(config('app.name') . ' - Google Drive');
        $this->client->setScopes([
            Drive::DRIVE_FILE,
            Drive::DRIVE_METADATA_READONLY,
        ]);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent');

        // Configurações de autenticação
        $credentials = $this->getCredentials();
        if ($credentials) {
            $this->client->setAuthConfig($credentials);
        }

        // Se temos tokens salvos, usar
        if ($this->settings && $this->settings->is_connected) {
            $this->setAccessToken();
        }

        return $this->client;
    }

    /**
     * Obtém credenciais do ambiente
     */
    protected function getCredentials(): ?array
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        $redirectUri = config('services.google.redirect');

        if (!$clientId || !$clientSecret) {
            return null;
        }

        return [
            'web' => [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uris' => [$redirectUri ?? route('google.callback')],
            ],
        ];
    }

    /**
     * Define o token de acesso no cliente
     */
    protected function setAccessToken(): void
    {
        if (!$this->settings) {
            return;
        }

        $accessToken = $this->settings->getDecryptedAccessToken();
        
        if (!$accessToken) {
            return;
        }

        $token = [
            'access_token' => $accessToken,
            'refresh_token' => $this->settings->getDecryptedRefreshToken(),
            'expires_in' => $this->settings->token_expires_at 
                ? $this->settings->token_expires_at->diffInSeconds(now()) 
                : 3600,
        ];

        $this->client->setAccessToken($token);

        // Refresh se necessário
        if ($this->client->isAccessTokenExpired()) {
            $this->refreshToken();
        }
    }

    /**
     * Renova o token de acesso
     */
    public function refreshToken(): bool
    {
        if (!$this->settings || !$this->client) {
            return false;
        }

        $refreshToken = $this->settings->getDecryptedRefreshToken();
        
        if (!$refreshToken) {
            return false;
        }

        try {
            $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
            $newToken = $this->client->getAccessToken();

            $this->settings->updateTokens(
                $newToken['access_token'],
                $newToken['refresh_token'] ?? null,
                $newToken['expires_in'] ?? 3600
            );

            return true;
        } catch (\Exception $e) {
            Log::error('Google Drive: Erro ao renovar token', [
                'error' => $e->getMessage(),
            ]);
            $this->settings->logError($e->getMessage());
            return false;
        }
    }

    /**
     * Obtém o serviço Drive
     */
    public function getDriveService(): Drive
    {
        if ($this->driveService) {
            return $this->driveService;
        }

        $this->driveService = new Drive($this->getClient());
        return $this->driveService;
    }

    // ==========================================
    // AUTENTICAÇÃO OAUTH
    // ==========================================

    /**
     * Gera URL de autenticação OAuth
     */
    public function getAuthUrl(): string
    {
        return $this->getClient()->createAuthUrl();
    }

    /**
     * Processa callback do OAuth
     */
    public function handleCallback(string $authCode): bool
    {
        try {
            $client = $this->getClient();
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

            if (isset($accessToken['error'])) {
                throw new \Exception($accessToken['error_description'] ?? 'Erro desconhecido');
            }

            // Salvar tokens
            if (!$this->settings) {
                $this->settings = GoogleDriveSetting::getForCurrentUser();
            }

            $this->settings->update([
                'access_token' => encrypt($accessToken['access_token']),
                'refresh_token' => isset($accessToken['refresh_token']) 
                    ? encrypt($accessToken['refresh_token']) 
                    : $this->settings->refresh_token,
                'token_expires_at' => now()->addSeconds($accessToken['expires_in'] ?? 3600),
                'is_connected' => true,
                'last_error' => null,
            ]);

            // Criar pasta raiz
            $this->createRootFolder();

            GoogleDriveActivityLog::logConnect();

            return true;
        } catch (\Exception $e) {
            Log::error('Google Drive: Erro no callback OAuth', [
                'error' => $e->getMessage(),
            ]);
            
            if ($this->settings) {
                $this->settings->logError($e->getMessage());
            }
            
            GoogleDriveActivityLog::logError($e->getMessage());
            return false;
        }
    }

    /**
     * Desconecta conta do Google Drive
     */
    public function disconnect(): bool
    {
        if (!$this->settings) {
            return false;
        }

        try {
            // Revogar token
            $accessToken = $this->settings->getDecryptedAccessToken();
            if ($accessToken) {
                $this->getClient()->revokeToken($accessToken);
            }
        } catch (\Exception $e) {
            // Ignora erro de revogação
        }

        $this->settings->disconnect();
        GoogleDriveActivityLog::logDisconnect();

        return true;
    }

    /**
     * Verifica se está conectado
     */
    public function isConnected(): bool
    {
        return $this->settings && $this->settings->is_connected;
    }

    // ==========================================
    // OPERAÇÕES DE PASTA
    // ==========================================

    /**
     * Cria pasta raiz no Drive
     */
    public function createRootFolder(): ?GoogleDriveFolder
    {
        $folderName = config('app.name') . ' - Documentos';
        
        try {
            $driveFolder = $this->createFolder($folderName);
            
            if (!$driveFolder) {
                return null;
            }

            // Salvar referência
            $this->settings->update([
                'root_folder_id' => $driveFolder->getId(),
                'root_folder_name' => $folderName,
            ]);

            // Criar registro da pasta
            return GoogleDriveFolder::updateOrCreate(
                [
                    'user_id' => $this->settings->user_id,
                    'folder_type' => 'root',
                ],
                [
                    'google_folder_id' => $driveFolder->getId(),
                    'name' => $folderName,
                    'web_view_link' => $driveFolder->getWebViewLink(),
                ]
            );
        } catch (\Exception $e) {
            Log::error('Google Drive: Erro ao criar pasta raiz', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Cria pasta no Drive
     */
    public function createFolder(string $name, ?string $parentId = null): ?DriveFile
    {
        try {
            $drive = $this->getDriveService();

            $folderMetadata = new DriveFile([
                'name' => $name,
                'mimeType' => 'application/vnd.google-apps.folder',
            ]);

            if ($parentId) {
                $folderMetadata->setParents([$parentId]);
            }

            return $drive->files->create($folderMetadata, [
                'fields' => 'id, name, webViewLink',
            ]);
        } catch (\Exception $e) {
            Log::error('Google Drive: Erro ao criar pasta', [
                'name' => $name,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Obtém ou cria pasta para uma entidade
     */
    public function getOrCreateFolderForEntity(Model $entity): ?GoogleDriveFolder
    {
        if (!$this->isConnected() || !$this->settings->root_folder_id) {
            return null;
        }

        // Determinar tipo de pasta e nome
        $folderType = match(class_basename($entity)) {
            'Client' => 'client',
            'Process' => 'process',
            'Contract' => 'contracts',
            'Invoice' => 'invoices',
            default => 'custom',
        };

        // Verificar se já existe
        $existingFolder = GoogleDriveFolder::forEntity($entity)->first();
        
        if ($existingFolder && $existingFolder->google_folder_id) {
            return $existingFolder;
        }

        // Criar nova pasta
        try {
            $folderName = GoogleDriveFolder::generateFolderName($entity, $folderType);
            
            // Determinar pasta pai
            $parentId = $this->settings->root_folder_id;
            
            // Se for processo, criar dentro da pasta do cliente
            if ($entity instanceof Process && $entity->client_id) {
                $clientFolder = $this->getOrCreateFolderForEntity($entity->client);
                if ($clientFolder) {
                    $parentId = $clientFolder->google_folder_id;
                }
            }

            $driveFolder = $this->createFolder($folderName, $parentId);
            
            if (!$driveFolder) {
                return null;
            }

            // Criar registro
            $folder = GoogleDriveFolder::updateOrCreate(
                [
                    'folderable_type' => get_class($entity),
                    'folderable_id' => $entity->id,
                ],
                [
                    'user_id' => $this->settings->user_id,
                    'google_folder_id' => $driveFolder->getId(),
                    'name' => $folderName,
                    'parent_folder_id' => $parentId,
                    'web_view_link' => $driveFolder->getWebViewLink(),
                    'folder_type' => $folderType,
                ]
            );

            return $folder;
        } catch (\Exception $e) {
            Log::error('Google Drive: Erro ao criar pasta para entidade', [
                'entity' => get_class($entity),
                'id' => $entity->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    // ==========================================
    // OPERAÇÕES DE ARQUIVO
    // ==========================================

    /**
     * Faz upload de arquivo para o Drive
     */
    public function uploadFile(
        string $localPath,
        string $fileName,
        ?string $folderId = null,
        ?string $mimeType = null
    ): ?DriveFile {
        if (!$this->isConnected()) {
            return null;
        }

        $fullPath = storage_path('app/' . $localPath);
        
        if (!file_exists($fullPath)) {
            Log::error('Google Drive: Arquivo local não encontrado', [
                'path' => $fullPath,
            ]);
            return null;
        }

        try {
            $drive = $this->getDriveService();

            $fileMetadata = new DriveFile([
                'name' => $fileName,
            ]);

            // Definir pasta pai
            $parentId = $folderId ?? $this->settings->root_folder_id;
            if ($parentId) {
                $fileMetadata->setParents([$parentId]);
            }

            // Detectar MIME type
            $mimeType = $mimeType ?? mime_content_type($fullPath);

            // Upload
            $driveFile = $drive->files->create($fileMetadata, [
                'data' => file_get_contents($fullPath),
                'mimeType' => $mimeType,
                'uploadType' => 'multipart',
                'fields' => 'id, name, mimeType, size, md5Checksum, webViewLink, webContentLink, modifiedTime',
            ]);

            return $driveFile;
        } catch (\Exception $e) {
            Log::error('Google Drive: Erro no upload', [
                'file' => $fileName,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Sincroniza arquivo local com o Drive
     */
    public function syncFile(GoogleDriveFile $file): bool
    {
        if (!$this->isConnected()) {
            $file->markAsFailed('Google Drive não conectado');
            return false;
        }

        $file->markAsSyncing();

        try {
            // Determinar pasta de destino
            $folderId = $this->settings->root_folder_id;
            
            if ($file->fileable) {
                $folder = $this->getOrCreateFolderForEntity($file->fileable);
                if ($folder) {
                    $folderId = $folder->google_folder_id;
                }
            }

            // Upload
            $driveFile = $this->uploadFile(
                $file->local_path,
                $file->name,
                $folderId,
                $file->mime_type
            );

            if (!$driveFile) {
                $file->markAsFailed('Falha no upload para o Drive');
                return false;
            }

            // Atualizar registro
            $file->markAsSynced([
                'google_file_id' => $driveFile->getId(),
                'google_folder_id' => $folderId,
                'web_view_link' => $driveFile->getWebViewLink(),
                'web_content_link' => $driveFile->getWebContentLink(),
                'drive_path' => $file->name,
                'drive_modified_at' => now(),
            ]);

            GoogleDriveActivityLog::logUpload($file);

            return true;
        } catch (\Exception $e) {
            $file->markAsFailed($e->getMessage());
            GoogleDriveActivityLog::logError($e->getMessage(), $file->name, $file->id);
            return false;
        }
    }

    /**
     * Sincroniza múltiplos arquivos
     */
    public function syncPendingFiles(int $limit = 10): int
    {
        $files = GoogleDriveFile::pending()
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        $synced = 0;

        foreach ($files as $file) {
            if ($this->syncFile($file)) {
                $synced++;
            }
        }

        if ($synced > 0) {
            $this->settings->logSuccessfulSync();
            GoogleDriveActivityLog::logSync($synced);
        }

        return $synced;
    }

    /**
     * Download arquivo do Drive
     */
    public function downloadFile(string $googleFileId, string $localPath): bool
    {
        if (!$this->isConnected()) {
            return false;
        }

        try {
            $drive = $this->getDriveService();
            $response = $drive->files->get($googleFileId, ['alt' => 'media']);
            
            $content = $response->getBody()->getContents();
            Storage::put($localPath, $content);

            return true;
        } catch (\Exception $e) {
            Log::error('Google Drive: Erro no download', [
                'fileId' => $googleFileId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Deleta arquivo do Drive
     */
    public function deleteFile(string $googleFileId): bool
    {
        if (!$this->isConnected()) {
            return false;
        }

        try {
            $drive = $this->getDriveService();
            $drive->files->delete($googleFileId);
            return true;
        } catch (\Exception $e) {
            Log::error('Google Drive: Erro ao deletar', [
                'fileId' => $googleFileId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    // ==========================================
    // SINCRONIZAÇÃO AUTOMÁTICA
    // ==========================================

    /**
     * Cria registro e enfileira para sync de um documento gerado
     */
    public function queueDocumentForSync(Model $document, string $localPath, string $fileName): GoogleDriveFile
    {
        $file = GoogleDriveFile::createFromLocal($document, $localPath, $fileName);

        // Se auto sync está ativo, sincronizar imediatamente
        if ($this->settings && $this->settings->auto_sync) {
            $this->syncFile($file);
        }

        return $file;
    }

    /**
     * Sincroniza documento gerado
     */
    public function syncGeneratedDocument(Model $document): ?GoogleDriveFile
    {
        if (!$this->shouldSync('documents')) {
            return null;
        }

        // Verificar se já tem arquivo local
        $filePath = $document->file_path ?? null;
        $fileName = $document->file_name ?? $document->title . '.pdf';

        if (!$filePath) {
            return null;
        }

        return $this->queueDocumentForSync($document, $filePath, $fileName);
    }

    /**
     * Sincroniza relatório gerado
     */
    public function syncGeneratedReport(Model $report): ?GoogleDriveFile
    {
        if (!$this->shouldSync('reports')) {
            return null;
        }

        $filePath = $report->file_path ?? null;
        $fileName = $report->file_name ?? 'relatorio.pdf';

        if (!$filePath) {
            return null;
        }

        return $this->queueDocumentForSync($report, $filePath, $fileName);
    }

    /**
     * Verifica se deve sincronizar tipo
     */
    protected function shouldSync(string $type): bool
    {
        if (!$this->isConnected() || !$this->settings) {
            return false;
        }

        return match($type) {
            'documents' => $this->settings->sync_documents,
            'reports' => $this->settings->sync_reports,
            'invoices' => $this->settings->sync_invoices,
            'contracts' => $this->settings->sync_contracts,
            default => false,
        };
    }

    // ==========================================
    // LISTAR ARQUIVOS
    // ==========================================

    /**
     * Lista arquivos na pasta raiz
     */
    public function listFiles(?string $folderId = null, int $pageSize = 100): array
    {
        if (!$this->isConnected()) {
            return [];
        }

        try {
            $drive = $this->getDriveService();
            
            $query = "trashed = false";
            $folderId = $folderId ?? $this->settings->root_folder_id;
            
            if ($folderId) {
                $query .= " and '{$folderId}' in parents";
            }

            $response = $drive->files->listFiles([
                'q' => $query,
                'pageSize' => $pageSize,
                'fields' => 'files(id, name, mimeType, size, modifiedTime, webViewLink, webContentLink)',
            ]);

            return $response->getFiles();
        } catch (\Exception $e) {
            Log::error('Google Drive: Erro ao listar arquivos', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Busca arquivos por nome
     */
    public function searchFiles(string $query): array
    {
        if (!$this->isConnected()) {
            return [];
        }

        try {
            $drive = $this->getDriveService();
            
            $response = $drive->files->listFiles([
                'q' => "name contains '{$query}' and trashed = false",
                'pageSize' => 50,
                'fields' => 'files(id, name, mimeType, size, modifiedTime, webViewLink)',
            ]);

            return $response->getFiles();
        } catch (\Exception $e) {
            Log::error('Google Drive: Erro na busca', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    // ==========================================
    // ESTATÍSTICAS
    // ==========================================

    /**
     * Obtém estatísticas de uso
     */
    public function getStats(): array
    {
        $totalFiles = GoogleDriveFile::count();
        $syncedFiles = GoogleDriveFile::synced()->count();
        $pendingFiles = GoogleDriveFile::pending()->count();
        $failedFiles = GoogleDriveFile::failed()->count();
        
        $totalSize = GoogleDriveFile::synced()->sum('size');

        return [
            'total_files' => $totalFiles,
            'synced_files' => $syncedFiles,
            'pending_files' => $pendingFiles,
            'failed_files' => $failedFiles,
            'sync_rate' => $totalFiles > 0 ? round(($syncedFiles / $totalFiles) * 100, 1) : 0,
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize),
            'is_connected' => $this->isConnected(),
            'last_sync' => $this->settings?->last_sync_at,
            'last_error' => $this->settings?->last_error,
        ];
    }

    /**
     * Formata bytes para leitura humana
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unit = 0;

        while ($bytes >= 1024 && $unit < count($units) - 1) {
            $bytes /= 1024;
            $unit++;
        }

        return round($bytes, 2) . ' ' . $units[$unit];
    }
}
