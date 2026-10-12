<?php

declare(strict_types=1);

namespace Src\Core;

/**
 * Prüft über den neuesten git-Tag eines Repositorys (GitHub, OneDev o.ä.),
 * ob ein Update der Software vorliegt.
 *
 * Bei GitHub wird die REST-API genutzt, bei anderen Servern das Git
 * Smart-HTTP-Protokoll (info/refs) - kein git-Binary auf dem Server nötig.
 */
final class UpdateChecker
{
    private string $repoUrl;
    private string $currentVersion;
    private int $cacheTtl;
    private string $cacheFile;
    private ?string $username;
    private ?string $password;

    /**
     * @param string      $repoUrl        Clone-URL des OneDev-Repositorys (https oder ssh)
     * @param string      $currentVersion Aktuell installierte Version (z.B. aus version.php)
     * @param int         $cacheTtl       Cache-Lebensdauer in Sekunden (0 = kein Cache)
     * @param string|null $cacheFile      Ablageort des Caches (Standard: System-Temp)
     * @param string|null $username       Optional: OneDev-Benutzer für private Repositories
     * @param string|null $password       Optional: OneDev-Passwort bzw. Access-Token
     */
    public function __construct(
        string $repoUrl,
        string $currentVersion,
        int $cacheTtl = 3600,
        ?string $cacheFile = null,
        ?string $username = null,
        ?string $password = null
    ) {
        $this->repoUrl = rtrim($repoUrl, '/');
        $this->currentVersion = $this->normalizeVersion($currentVersion);
        $this->cacheTtl = $cacheTtl;
        $this->cacheFile = $cacheFile ?? sys_get_temp_dir() . '/meetlog_latest_tag.json';
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Erzeugt einen UpdateChecker aus der Konfiguration ([update]-Sektion der meetlog.ini).
     * Liefert null, wenn keine Repo-URL konfiguriert ist.
     */
    public static function fromConfig(?Config $config, string $currentVersion): ?self
    {
        if ($config === null) {
            return null;
        }

        $repoUrl = trim((string) $config->get('update', 'repo_url', ''));
        if ($repoUrl === '') {
            return null;
        }

        $username = trim((string) $config->get('update', 'user', ''));
        $password = (string) $config->get('update', 'pass', '');

        return new self(
            $repoUrl,
            $currentVersion,
            (int) $config->get('update', 'cache_ttl', 3600),
            null,
            $username !== '' ? $username : null,
            $password !== '' ? $password : null
        );
    }

    /**
     * Ermittelt den neuesten Versions-Tag des Repositorys.
     * Liefert null, wenn der Abruf nicht möglich ist oder keine Versions-Tags existieren.
     */
    public function getLatestTag(bool $useCache = true): ?string
    {
        if ($useCache && $this->cacheTtl > 0) {
            $cached = $this->readCache();
            if ($cached !== null) {
                return $cached !== '' ? $cached : null;
            }
        }

        // Bei GitHub-URLs primär die REST-API nutzen, danach Smart-HTTP (info/refs),
        // als letzter Fallback ein lokal installiertes git-Binary
        $tags = $this->fetchTagsViaGithubApi();
        if (empty($tags)) {
            $tags = $this->fetchTagsViaHttp();
        }
        if (empty($tags)) {
            $tags = $this->fetchTagsViaGitCli();
        }

        // Nur Versions-Tags berücksichtigen (z.B. 1.2.3 oder v1.2.3)
        $tags = array_values(array_filter(
            $tags,
            fn(string $tag): bool => (bool) preg_match('/^v?\d+\.\d+(\.\d+)?([-.][0-9A-Za-z.]+)?$/', $tag)
        ));

        if (empty($tags)) {
            // Auch Fehlschläge cachen, damit nicht jeder Seitenaufruf blockiert
            if ($this->cacheTtl > 0) {
                $this->writeCache('');
            }
            return null;
        }

        usort(
            $tags,
            fn(string $a, string $b): int => version_compare(
                $this->normalizeVersion($a),
                $this->normalizeVersion($b)
            )
        );

        $latest = (string) end($tags);

        if ($this->cacheTtl > 0) {
            $this->writeCache($latest);
        }

        return $latest;
    }

    /**
     * Liefert die URL zur Release-/Tag-Seite des Repositorys für einen Tag.
     */
    public function getReleaseUrl(string $tag): string
    {
        $repoPath = $this->extractGithubRepoPath();
        if ($repoPath !== null) {
            return 'https://github.com/' . $repoPath . '/releases/tag/' . rawurlencode($tag);
        }

        return $this->repoUrl;
    }

    /**
     * Liest die Tags über die GitHub-REST-API (nur bei GitHub-URLs).
     */
    private function fetchTagsViaGithubApi(): array
    {
        $repoPath = $this->extractGithubRepoPath();
        if ($repoPath === null) {
            return [];
        }

        $url = 'https://api.github.com/repos/' . $repoPath . '/tags?per_page=100';

        $headers = [
            'User-Agent: meetlog-update-checker',
            'Accept: application/vnd.github+json',
        ];
        if ($this->username !== null) {
            $headers[] = 'Authorization: Basic ' . base64_encode($this->username . ':' . (string) $this->password);
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => 5,
                'ignore_errors' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);
        if ($body === false || $body === '') {
            return [];
        }

        $status = 0;
        foreach ($http_response_header ?? [] as $headerLine) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $headerLine, $m)) {
                $status = (int) $m[1];
            }
        }

        if ($status !== 200) {
            return [];
        }

        $data = json_decode($body, true);
        if (!is_array($data)) {
            return [];
        }

        $tags = [];
        foreach ($data as $entry) {
            if (is_array($entry) && isset($entry['name']) && is_string($entry['name'])) {
                $tags[] = $entry['name'];
            }
        }

        return $tags;
    }

    /**
     * Extrahiert "owner/repo" aus einer GitHub-URL, liefert null bei anderen Hosts.
     */
    private function extractGithubRepoPath(): ?string
    {
        if (preg_match('#^https?://github\.com/([^/]+)/([^/]+?)(?:\.git)?$#i', $this->repoUrl, $m)) {
            return $m[1] . '/' . $m[2];
        }

        return null;
    }

    /**
     * Liest die Tags über das Git Smart-HTTP-Protokoll (info/refs).
     * Funktioniert mit jedem Git-HTTP-Server (auch OneDev), ganz ohne git-Binary.
     */
    private function fetchTagsViaHttp(): array
    {
        if (!str_starts_with($this->repoUrl, 'http://') && !str_starts_with($this->repoUrl, 'https://')) {
            return [];
        }

        $url = $this->repoUrl . '/info/refs?service=git-upload-pack';

        $headers = ['User-Agent: meetlog-update-checker'];
        if ($this->username !== null) {
            $headers[] = 'Authorization: Basic ' . base64_encode($this->username . ':' . (string) $this->password);
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => 5,
                'ignore_errors' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);
        if ($body === false || $body === '') {
            return [];
        }

        $status = 0;
        foreach ($http_response_header ?? [] as $headerLine) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $headerLine, $m)) {
                $status = (int) $m[1];
            }
        }

        if ($status !== 200) {
            return [];
        }

        return $this->parsePktLineTags($body);
    }

    /**
     * Fallback: Liest die Tags über ein lokal installiertes git-Binary.
     */
    private function fetchTagsViaGitCli(): array
    {
        if (!is_callable('exec')) {
            return [];
        }

        $command = sprintf(
            'git ls-remote --tags --refs %s 2>/dev/null',
            escapeshellarg($this->repoUrl)
        );

        $output = [];
        $exitCode = 1;
        exec($command, $output, $exitCode);

        if ($exitCode !== 0 || empty($output)) {
            return [];
        }

        $tags = [];
        foreach ($output as $line) {
            // Format: "<sha>\trefs/tags/<tag>"
            if (preg_match('#refs/tags/(\S+)$#', $line, $m)) {
                $tags[] = $m[1];
            }
        }

        return $tags;
    }

    /**
     * Parst die pkt-line-Antwort von info/refs und liefert alle Tag-Namen.
     */
    private function parsePktLineTags(string $body): array
    {
        $tags = [];
        $offset = 0;
        $length = strlen($body);

        while ($offset + 4 <= $length) {
            $lineLength = hexdec(substr($body, $offset, 4));
            $offset += 4;

            if ($lineLength === 0) {
                continue; // flush-pkt
            }
            if ($lineLength < 4 || $offset + $lineLength - 4 > $length) {
                break; // ungültige Antwort
            }

            $payload = substr($body, $offset, $lineLength - 4);
            $offset += $lineLength - 4;

            // Capabilities hinter dem ersten NUL-Byte abschneiden
            $nulPos = strpos($payload, "\0");
            if ($nulPos !== false) {
                $payload = substr($payload, 0, $nulPos);
            }

            if (
                preg_match('#^[0-9a-f]{40}\s+refs/tags/(\S+)$#', trim($payload), $m)
                && !str_ends_with($m[1], '^{}')
            ) {
                $tags[] = $m[1];
            }
        }

        return $tags;
    }

    /**
     * True, wenn der neueste Tag eine höhere Version als die installierte ist.
     */
    public function isUpdateAvailable(): bool
    {
        $latest = $this->getLatestTag();

        if ($latest === null) {
            return false;
        }

        return version_compare($this->normalizeVersion($latest), $this->currentVersion, '>');
    }

    /**
     * Entfernt ein führendes "v" für den Versionsvergleich.
     */
    private function normalizeVersion(string $version): string
    {
        return ltrim(trim($version), 'vV');
    }

    private function readCache(): ?string
    {
        if (!is_file($this->cacheFile)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($this->cacheFile), true);

        if (!is_array($data) || !array_key_exists('tag', $data) || empty($data['fetched'])) {
            return null;
        }

        if (time() - (int) $data['fetched'] > $this->cacheTtl) {
            return null;
        }

        return (string) $data['tag'];
    }

    private function writeCache(string $tag): void
    {
        @file_put_contents(
            $this->cacheFile,
            json_encode(['tag' => $tag, 'fetched' => time()])
        );
    }
}
