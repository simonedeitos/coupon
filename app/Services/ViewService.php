<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class ViewService
{
    public function __construct(private readonly string $basePath)
    {
    }

    public function render(string $view, array $data = [], string $layout = 'app'): string
    {
        $content = $this->partial($view, $data);
        $layoutFile = $this->basePath . '/layouts/' . $layout . '.php';
        if (! is_file($layoutFile)) {
            throw new RuntimeException('Layout not found: ' . $layout);
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $layoutFile;
        return (string) ob_get_clean();
    }

    public function partial(string $view, array $data = []): string
    {
        $viewFile = $this->basePath . '/' . $view . '.php';
        if (! is_file($viewFile)) {
            throw new RuntimeException('View not found: ' . $view);
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $viewFile;
        return (string) ob_get_clean();
    }
}
