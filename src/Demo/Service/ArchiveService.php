<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Demo\Service;

/**
 * Service for importing/exporting lists.ini and referenced files as ZIP or JSON.
 */
final class ArchiveService
{
    /**
     * @param ListStore $store
     */
    public function __construct(private ListStore $store)
    { }

    /**
     * Export ZIP (or JSON as fallback).
     *
     * @return array{filename:string,content:string,ctype:string}
     */
    public function exportZip(): array
    {
        if (!class_exists(\ZipArchive::class)) {
            // Fallback: JSON export (same shape as /lists GET)
            $data = $this->store->load();
            return [
                'filename' => 'email-lists.json',
                'content'  => json_encode(['ini' => $data['ini'], 'files' => $data['files']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'ctype'    => 'application/json',
            ];
        }

        $zip = new \ZipArchive();
        $tmp = tempnam(sys_get_temp_dir(), 'elist_') ?: (sys_get_temp_dir() . '/elist_' . uniqid());
        if ($zip->open($tmp, \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Cannot create zip');
        }

        // Add lists.ini
        $ini = is_file($this->store->iniPath()) ? (string)file_get_contents($this->store->iniPath()) : '';
        $zip->addFromString('lists.ini', $ini);

        // Add referenced files (preserve relative paths)
        foreach ($this->store->referencedRelPaths() as $rel) {
            $full = $this->store->resolve($rel);
            $content = is_file($full) ? (string)file_get_contents($full) : '';
            $zip->addFromString($rel, $content);
        }

        $zip->close();
        $blob = (string)file_get_contents($tmp);
        @unlink($tmp);

        return [
            'filename' => 'email-lists.zip',
            'content'  => $blob,
            'ctype'    => 'application/zip',
        ];
    }

    /**
     * Import ZIP (or JSON as fallback). Returns summary.
     *
     * @param string $tmpFile Temporary uploaded file path
     * @param string $contentType The Content-Type of the uploaded file
     * @return array{ini:bool, files:int} Number of files written; ini=true if lists.ini was found and written
     */
    public function importFromUpload(string $tmpFile, string $contentType): array
    {
        if (stripos($contentType, 'application/json') !== false) {
            $data = json_decode((string)file_get_contents($tmpFile), true);
            if (!is_array($data)) {
                throw new \RuntimeException('Invalid JSON');
            }
            /** @var string $ini */
            $ini = (string)($data['ini'] ?? '');
            /** @var array<string, array{path:string,content:string}> $files */
            $files = (array)($data['files'] ?? []);
            $this->store->save($ini, $files);
            return ['ini' => true, 'files' => count($files)];
        }

        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('ZipArchive not available; upload JSON instead.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($tmpFile) !== true) {
            throw new \RuntimeException('Cannot open archive');
        }

        $iniWritten = false;
        $filesWritten = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (!$stat) {
                continue;
            }
            $name = $stat['name'];
            // Normalize & guard
            $name = str_replace(['\\','/'], '/', $name);
            $name = ltrim($name, '/');
            if ($name === '' || str_contains($name, '../')) {
                continue;
            }

            $content = (string)$zip->getFromIndex($i);

            if (strtolower($name) === 'lists.ini') {
                $iniWritten = true;
                $iniPath = $this->store->iniPath();
                if (!is_dir(dirname($iniPath))) {
                    @mkdir(dirname($iniPath), 0777, true);
                }
                file_put_contents($iniPath, $content);
                continue;
            }

            // Only allow text files under subfolders; keep the relative path
            if (!preg_match('~^[A-Za-z0-9_\-./]+\.txt$~', $name)) {
                continue;
            }
            $full = $this->store->resolve($name);
            if (!is_dir(dirname($full))) {
                @mkdir(dirname($full), 0777, true);
            }
            file_put_contents($full, $content);
            $filesWritten++;
        }

        $zip->close();
        return ['ini' => $iniWritten, 'files' => $filesWritten];
    }
}
