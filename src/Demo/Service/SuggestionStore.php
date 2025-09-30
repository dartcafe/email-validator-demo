<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Demo\Service;

final class SuggestionStore
{
    public function __construct(private string $configDir)
    {
        $this->configDir = rtrim($configDir, DIRECTORY_SEPARATOR);
    }

    public function path(): string
    {
        return $this->configDir . DIRECTORY_SEPARATOR . 'suggestions.txt';
    }

    /** @return list<string> */
    public function load(): array
    {
        $file = $this->path();
        if (!is_file($file)) {
            return [];
        }
        $lines = preg_split('/\R/u', (string)file_get_contents($file)) ?: [];
        $out = [];
        foreach ($lines as $ln) {
            $ln = strtolower(trim($ln));
            if ($ln === '' || $ln[0] === '#') {
                continue;
            }
            $out[] = $ln;
        }
        return array_values(array_unique($out));
    }

    /** @param list<string> $domains */
    public function save(array $domains): void
    {
        if (!is_dir($this->configDir)) {
            @mkdir($this->configDir, 0777, true);
        }
        $text = implode("\n", array_map(static fn (string $d): string => strtolower(trim($d)), $domains)) . "\n";
        file_put_contents($this->path(), $text);
    }
}
