<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Вспомогательный класс для мокирования php://input в тестах
 * Позволяет подменять входной поток для тестирования POST запросов с JSON
 */
class MockPhpInputStream
{
    public static string $data = '';
    private int $position = 0;

    public function stream_open(string $path, string $mode, int $option, ?string &$open_path): bool
    {
        if ($path === 'php://input') {
            $this->position = 0;
            return true;
        }
        return false;
    }

    public function stream_read(int $count): string
    {
        $chunk = substr(self::$data, $this->position, $count);
        $this->position += strlen($chunk);
        return $chunk;
    }

    public function stream_eof(): bool
    {
        return $this->position >= strlen(self::$data);
    }

    public function stream_stat(): array
    {
        // Минимально допустимый stat
        return [];
    }
}