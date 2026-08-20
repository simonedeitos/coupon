<?php

declare(strict_types=1);

namespace App\Services;

final class CacheService
{
    public function __construct(private readonly string $storagePath)
    {
        foreach (['cache', 'logs', 'sitemaps'] as $segment) {
            if (! is_dir($this->storagePath . '/' . $segment)) {
                mkdir($this->storagePath . '/' . $segment, 0755, true);
            }
        }
    }

    public function collection(string $name, array $seed = []): array
    {
        $file = $this->path('cache', $name . '.json');
        if (! is_file($file)) {
            return $seed;
        }
        $decoded = json_decode((string) file_get_contents($file), true);
        return is_array($decoded) ? $decoded : $seed;
    }

    public function putCollection(string $name, array $data): void
    {
        file_put_contents($this->path('cache', $name . '.json'), json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function appendJsonLine(string $segment, string $fileName, array $record): void
    {
        file_put_contents($this->path($segment, $fileName), json_encode($record, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
    }

    public function readJsonLines(string $segment, string $fileName, ?int $limit = 200): array
    {
        $file = $this->path($segment, $fileName);
        if (! is_file($file)) {
            return [];
        }
        $lines = array_filter(explode(PHP_EOL, trim((string) file_get_contents($file))));
        if ($limit !== null) {
            $lines = array_slice($lines, -$limit);
        }
        return array_values(array_filter(array_map(static fn (string $line): ?array => json_decode($line, true), $lines)));
    }

    public function writeFile(string $segment, string $fileName, string $content): void
    {
        file_put_contents($this->path($segment, $fileName), $content);
    }

    public function readFile(string $segment, string $fileName): ?string
    {
        $file = $this->path($segment, $fileName);
        return is_file($file) ? (string) file_get_contents($file) : null;
    }

    private function path(string $segment, string $fileName): string
    {
        return $this->storagePath . '/' . trim($segment, '/') . '/' . ltrim($fileName, '/');
    }
}
