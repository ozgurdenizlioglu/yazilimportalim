<?php

declare(strict_types=1);

namespace App\Core;

class Controller
{
    protected function view(string $view, array $data = [], ?string $layout = 'layouts/base'): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            http_response_code(500);
            echo "View not found: $view";
            return;
        }

        ob_start();
        include $viewFile;
        $content = ob_get_clean();

        if ($layout) {
            $layoutFile = __DIR__ . '/../Views/' . $layout . '.php';
            if (!file_exists($layoutFile)) {
                http_response_code(500);
                echo "Layout not found: $layout";
                return;
            }
            include $layoutFile;
        } else {
            echo $content;
        }
    }
}
