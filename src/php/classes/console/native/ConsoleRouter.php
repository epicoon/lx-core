<?php

namespace lx;

use lx;

class ConsoleRouter implements RouterInterface, FusionComponentInterface
{
    use FusionComponentTrait;

    protected array $routes = [];

    public function route(string $route): ?ResourceContextInterface
    {
        $executor = $this->getExecutor($this->routes, $route);
        if ($executor) {
            return new ConsoleResourceContext([
                'executor' => $executor,
            ]);
        }
        
        foreach ($this->routes as $routeKey => $routeDef) {
            if ($routeKey[0] == '!') {
                $prefix = trim($routeKey, '!');
                if (preg_match('/^' . $prefix . '/', $route)) {
                    $service = lx::$app->getService($routeDef);
                    $routeResidue = preg_replace('/^' . $prefix . '\\//', '', $route);
                    $executor = $this->getExecutor($service->getConfig('commands') ?? [], $routeResidue);
                    if ($executor) {
                        return new ConsoleResourceContext([
                            'executor' => $executor,
                        ]);
                    }
                }
            }
        }

        // Если не найдено
        return new ConsoleResourceContext([
            'executor' => DefaultCommand::class,
        ]);
    }

    public function getAssetPrefix(): string
    {
        return '';
    }
    
    private function getExecutor($list, $key)
    {
        if (!array_key_exists($key, $list)) {
            return null;
        }

        $config = $list[$key];
        $executor = is_string($config)
            ? $config
            : $config['executor'] ?? null;
        return $executor;
    }
}
