<?php declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DefaultController
{
    public const DEFAULT_LANGUAGE = 'en-GB';

    public function index(): JsonResponse
    {
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    protected static function getTemplateDir(): string
    {
        return dirname(__DIR__, 2) . '/templates';
    }

    public static function renderTemplate(string $templateName, array $variables): void
    {
        extract($variables);
        require sprintf('%s/%sTemplate.php', self::getTemplateDir(), $templateName);
        $functionName = 'get' . $templateName;
        if (is_callable($functionName)) {
            echo $functionName(...$variables);
        }
    }

    public static function render(string $templateName, array $variables): Response
    {
        return new StreamedResponse(function () use ($templateName, $variables) {
            self::renderTemplate($templateName, $variables);
        });
    }

    public static function renderFunction(string $templateName, array $variables): string
    {
        $functionName = 'get' . $templateName;
        if (!is_callable($functionName)) {
            require sprintf('%s/%sFunction.php', self::getTemplateDir(), $templateName);
        }

        try {
            ob_start();
            $renderName = 'render' . $templateName;
            $result = $renderName(...$variables) ?? ob_get_contents();
            if ($result instanceof \Traversable) {
                return implode('', iterator_to_array($result));
            }
            return $result;
        } finally {
            ob_end_clean();
        }
    }
}