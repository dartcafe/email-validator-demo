<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Demo\Service;

use RuntimeException;

/**
 * Service for loading/saving lists.ini and all referenced files.
 */
final class ListStore
{
    /**
     * @param string $configDir Directory where lists.ini and referenced files are stored
     */
    public function __construct(private string $configDir)
    {
        $this->configDir = rtrim($configDir, DIRECTORY_SEPARATOR);
    }

    public function configDir(): string
    {
        return $this->configDir;
    }
    public function iniPath(): string
    {
        return $this->configDir . '/lists.ini';
    }

    /**
     * Load lists.ini and all referenced files
     *
     * @return array{ini:string, files: array<string, array{path:string, fullPath:string, content:string}>}
     */
    public function load(): array
    {
        $iniText = is_file($this->iniPath()) ? (string)file_get_contents($this->iniPath()) : '';
        $refs = $this->refsFromIni($iniText);

        $files = [];
        foreach ($refs as $section => $rel) {
            $full = $this->resolve($rel);
            $content = is_file($full) ? (string)file_get_contents($full) : '';
            $files[$section] = ['path' => $rel, 'fullPath' => $full, 'content' => $content];
        }
        return ['ini' => $iniText, 'files' => $files];
    }

    /**
     * Save lists.ini and all referenced files
     *
     * @param array<string, array{path:string,content:string}> $files
     */
    public function save(string $iniText, array $files): void
    {
        if (!is_dir($this->configDir)) {
            @mkdir($this->configDir, 0777, true);
        }
        file_put_contents($this->iniPath(), $iniText);

        foreach ($files as $info) {
            $rel = $this->normalizeRel($info['path']);
            $full = $this->resolve($rel);
            if (!is_dir(dirname($full))) {
                @mkdir(dirname($full), 0777, true);
            }
            file_put_contents($full, (string)$info['content']);
        }
    }

    /**
     * Get all relative paths referenced by lists.ini
     *
     * @return list<string> relative paths referenced by lists.ini
     */
    public function referencedRelPaths(): array
    {
        $ini = is_file($this->iniPath()) ? (string)file_get_contents($this->iniPath()) : '';
        return array_values($this->refsFromIni($ini));
    }

    /**
     * Parse lists.ini and get all referenced files
     *
     * @return array<string,string> section => relative path
     */
    private function refsFromIni(string $iniText): array
    {
        $refs = [];
        if ($iniText === '') {
            return $refs;
        }
        $sections = @parse_ini_string($iniText, true, INI_SCANNER_TYPED);
        if (!is_array($sections)) {
            return $refs;
        }

        foreach ($sections as $section => $kv) {
            if (!is_array($kv)) {
                continue;
            }
            $kv = array_change_key_case($kv, CASE_LOWER);
            $file = (string)($kv['listfilename'] ?? '');
            if ($file !== '' && (str_starts_with($file, 'config/') || str_starts_with($file, 'config\\'))) {
                $file = substr($file, 7);
            }
            if ($file !== '') {
                $refs[(string)$section] = $file;
            }
        }
        return $refs;
    }

    /**
     * Resolve a relative path to an absolute path within the config dir
     *
     * @throws RuntimeException if the path escapes the config dir or is unsafe
     */
    public function resolve(string $relative): string
    {
        $relative = $this->normalizeRel($relative);
        $base = $this->configDir . DIRECTORY_SEPARATOR;
        $full = $base . ltrim($relative, DIRECTORY_SEPARATOR);
        $real = realpath($full);
        if ($real === false) {
            return $full;
        }
        if (strpos($real, $base) !== 0) {
            throw new RuntimeException('Path escapes config dir: ' . $relative);
        }
        return $real;
    }

    /**
     * Normalize a relative path: convert slashes, remove leading config/, check for ..
     *
     * @throws RuntimeException if the path is unsafe
     */
    private function normalizeRel(string $relative): string
    {
        $relative = str_replace(['\\','/'], DIRECTORY_SEPARATOR, $relative);
        if (str_starts_with($relative, 'config' . DIRECTORY_SEPARATOR)) {
            $relative = substr($relative, 7);
        }
        if (str_contains($relative, '..')) {
            throw new RuntimeException('Unsafe relative path: ' . $relative);
        }
        return $relative;
    }
}
