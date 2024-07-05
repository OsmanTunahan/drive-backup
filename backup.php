<?php
set_time_limit(0);
ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);

class BackupTool {
    private $configFile;
    private $logFile;
    private $languages;
    private $lang;

    public function __construct() {
        $this->configFile = __DIR__ . '/config.json';
        $this->logFile = __DIR__ . '/backup.log';
        $this->languages = [
            'en' => [
                'error' => 'Error',
                'info' => 'Info',
                'success' => 'Success',
                'server_folder_invalid' => 'The server backup folder is invalid.',
                'settings_saved' => 'Settings saved successfully.',
                'step_two_text' => 'Please authorize the application by visiting this URL: %s',
                'reset_success' => 'Settings have been reset successfully.',
            ],
        ];
        $this->lang = 'en';
    }

    public function run() {
        if (isset($_GET['cron'])) {
            $this->performBackup();
        } else {
            $this->renderPage();
        }
    }

    private function performBackup() {
        $settings = $this->loadSettings();
        if (!$settings) {
            $this->printLog('ERROR', 'Settings could not be loaded.');
            return;
        }

        $zipFile = $this->createBackupZip($settings['server_backup_folder']);
        if (!$zipFile) {
            $this->printLog('ERROR', 'Failed to create backup zip file.');
            return;
        }

        $this->uploadToGoogleDrive($settings, $zipFile);
    }

    private function loadSettings() {
        if (!file_exists($this->configFile)) {
            return false;
        }
        $settingsData = json_decode(file_get_contents($this->configFile), true);
        return $settingsData;
    }

    private function createBackupZip($folder) {
        $zipFile = __DIR__ . '/backup_' . date('Y-m-d_H-i-s') . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE) !== TRUE) {
            return false;
        }

        $folder = realpath($folder);
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder), RecursiveIteratorIterator::LEAVES_ONLY);

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($folder) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();
        return $zipFile;
    }

    private function uploadToGoogleDrive($settings, $zipFile) {
        $client = new Google_Client();
        $client->setClientId($settings['google_client_id']);
        $client->setClientSecret($settings['google_client_secret']);
        $client->refreshToken($settings['google_refresh_token']);

        $service = new Google_Service_Drive($client);
        $file = new Google_Service_Drive_DriveFile();
        $file->setName(basename($zipFile));
        $file->setParents([$settings['google_drive_folder_id']]);

        $content = file_get_contents($zipFile);
        $service->files->create($file, [
            'data' => $content,
            'mimeType' => 'application/zip',
            'uploadType' => 'multipart'
        ]);

        $this->printLog('INFO', 'Backup uploaded to Google Drive successfully.');
        unlink($zipFile);
    }

    private function saveSettings($settingsData) {
        $path = realpath($settingsData['server_backup_folder']);
        if (!$path) {
            $this->setAlert('danger', '<b>' . $this->lang('error') . ':</b> ' . $this->lang('server_folder_invalid'));
            return;
        } else {
            $settingsData['server_backup_folder'] = $path . '/';
        }
        file_put_contents($this->configFile, json_encode($settingsData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $this->printLog('INFO', 'Settings saved successfully.');
        $this->setAlert('info', '<b>' . $this->lang('info') . ':</b> ' . sprintf($this->lang('step_two_text'), $this->getGoogleAuthUrl($settingsData)));
    }

    private function getGoogleAuthUrl($settingsData) {
        return sprintf('https://accounts.google.com/o/oauth2/auth?response_type=code&client_id=%s&redirect_uri=urn:ietf:wg:oauth:2.0:oob&scope=https://www.googleapis.com/auth/drive.file', $settingsData['google_client_id']);
    }

    private function resetSettings() {
        if (file_exists($this->configFile)) {
            unlink($this->configFile);
        }
        $this->setAlert('success', '<b>' . $this->lang('success') . ':</b> ' . $this->lang('reset_success'));
        header('Location: ' . $_SERVER['REQUEST_URI']);
        die();
    }

    private function setAlert($type, $message) {
        echo '<div class="alert alert-' . $type . '">' . $message . '</div>';
    }

    private function lang($key) {
        return $this->languages[$this->lang][$key] ?? $key;
    }

    private function getCronUrl() {
        return $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '?cron=true';
    }

    private function renderPage() {
        $settings = $this->loadSettings();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Backup Tool Settings</title>
            <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
        </head>
        <body>
        <div class="container">
            <h1>Backup Tool Settings</h1>
            <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                <div class="form-group">
                    <label for="server_backup_folder">Server Backup Folder</label>
                    <input type="text" class="form-control" id="server_backup_folder" name="server_backup_folder" value="<?php echo htmlspecialchars($settings['server_backup_folder'] ?? '', ENT_QUOTES); ?>">
                </div>
                <div class="form-group">
                    <label for="google_client_id">Google Client ID</label>
                    <input type="text" class="form-control" id="google_client_id" name="google_client_id" value="<?php echo htmlspecialchars($settings['google_client_id'] ?? '', ENT_QUOTES); ?>">
                </div>
                <div class="form-group">
                    <label for="google_client_secret">Google Client Secret</label>
                    <input type="text" class="form-control" id="google_client_secret" name="google_client_secret" value="<?php echo htmlspecialchars($settings['google_client_secret'] ?? '', ENT_QUOTES); ?>">
                </div>
                <div class="form-group">
                    <label for="google_refresh_token">Google Refresh Token</label>
                    <input type="text" class="form-control" id="google_refresh_token" name="google_refresh_token" value="<?php echo htmlspecialchars($settings['google_refresh_token'] ?? '', ENT_QUOTES); ?>">
                </div>
                <div class="form-group">
                    <label for="google_drive_folder_id">Google Drive Folder ID</label>
                    <input type="text" class="form-control" id="google_drive_folder_id" name="google_drive_folder_id" value="<?php echo htmlspecialchars($settings['google_drive_folder_id'] ?? '', ENT_QUOTES); ?>">
                </div>
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </form>
            <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                <input type="hidden" name="reset" value="1">
                <button type="submit" class="btn btn-danger">Reset Settings</button>
            </form>
        </div>
        </body>
        </html>
        <?php
    }

    private function printLog($type, $message) {
        $logMessage = date('Y-m-d H:i:s') . " [$type] $message\n";
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }
}

$backupTool = new BackupTool();
$backupTool->run();

?>