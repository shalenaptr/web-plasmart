<?php
declare(strict_types=1);
if (!defined('PLASMART_ADMIN_CONTEXT')) { http_response_code(403); exit; }

// Fallback stays inside open_basedir. Every stored session is guarded PHP,
// including when the web server does not process .htaccess.
final class PlasmartSessionStorage implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface {
    private const PREFIX = "<?php http_response_code(403); exit; ?>\n";
    private $handle = null;
    private string $currentId = '';
    public function __construct(private string $directory) {}
    private function path(string $id): string {
        return preg_match('/\A[a-zA-Z0-9,-]{22,256}\z/D', $id)
            ? $this->directory.'/session_'.$id.'.php' : '';
    }
    public function open(string $path, string $name): bool {
        return is_dir($this->directory) && is_writable($this->directory);
    }
    public function close(): bool {
        if (is_resource($this->handle)) { flock($this->handle, LOCK_UN); fclose($this->handle); }
        $this->handle = null; $this->currentId = ''; return true;
    }
    public function read(string $id): string|false {
        $file = $this->path($id); if ($file === '') return false;
        $this->close(); $this->handle = @fopen($file, 'c+b');
        if (!is_resource($this->handle) || !flock($this->handle, LOCK_EX)) { $this->close(); return false; }
        $this->currentId = $id;
        if (function_exists('chmod')) @chmod($file, 0600);
        $raw = stream_get_contents($this->handle);
        if ($raw === '') {
            if (fwrite($this->handle, self::PREFIX) !== strlen(self::PREFIX)) return false;
            return '';
        }
        return is_string($raw) && str_starts_with($raw, self::PREFIX)
            ? substr($raw, strlen(self::PREFIX)) : false;
    }
    public function write(string $id, string $data): bool {
        if ($this->currentId !== $id && $this->read($id) === false) return false;
        if (!is_resource($this->handle)) return false;
        $raw = self::PREFIX.$data;
        return rewind($this->handle) && ftruncate($this->handle, 0)
            && fwrite($this->handle, $raw) === strlen($raw) && fflush($this->handle);
    }
    public function destroy(string $id): bool {
        $file = $this->path($id); if ($file === '') return false;
        if ($this->currentId === $id) $this->close();
        return !is_file($file) || @unlink($file);
    }
    public function validateId(string $id): bool {
        $file = $this->path($id); if ($file === '' || !is_file($file)) return false;
        $raw = @file_get_contents($file, false, null, 0, strlen(self::PREFIX));
        return $raw === self::PREFIX;
    }
    public function updateTimestamp(string $id, string $data): bool { return $this->write($id, $data); }
    public function gc(int $max_lifetime): int|false {
        $count = 0; $cutoff = time() - $max_lifetime;
        foreach (new DirectoryIterator($this->directory) as $entry) {
            if (!$entry->isFile() || !preg_match('/\Asession_[a-zA-Z0-9,-]{22,256}\.php\z/D', $entry->getFilename())
                || $entry->getMTime() >= $cutoff || $entry->getFilename() === 'session_'.$this->currentId.'.php') continue;
            $handle = @fopen($entry->getPathname(), 'r+b');
            if (!is_resource($handle)) continue;
            // Never remove another request's locked session.
            if (flock($handle, LOCK_EX | LOCK_NB)) {
                $stat = fstat($handle);
                if ($stat && $stat['mtime'] < $cutoff && @unlink($entry->getPathname())) $count++;
                flock($handle, LOCK_UN);
            }
            fclose($handle);
        }
        return $count;
    }
}

function admin_session_unavailable(): never {
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    exit('<!doctype html><html lang="id"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Sesi Admin</title><p>Sesi admin belum dapat disimpan. Periksa izin tulis folder data pada hosting, lalu coba kembali.</p></html>');
}
function prepare_admin_session_storage(): void {
    if (session_module_name() !== 'files') return;
    $path = session_save_path(); $path = substr($path, strrpos($path, ';') === false ? 0 : strrpos($path, ';') + 1);
    if ($path === '') $path = function_exists('sys_get_temp_dir') ? sys_get_temp_dir() : (string)ini_get('sys_temp_dir');
    if ($path !== '' && @is_dir($path) && @is_writable($path)) return;
    $directory = __DIR__.'/../data/.sessions';
    if (!is_dir($directory) && !@mkdir($directory, 0700, true)) admin_session_unavailable();
    if (!is_writable($directory) || !session_set_save_handler(new PlasmartSessionStorage($directory), true)) admin_session_unavailable();
}
