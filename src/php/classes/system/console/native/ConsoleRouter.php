<?php

namespace lx;

class ConsoleRouter implements RouterInterface, FusionComponentInterface
{
    use FusionComponentTrait;

    protected array $routes = [];

    public function route(string $route): ?ResourceContextInterface
    {
        if (array_key_exists($route, $this->routes)) {
            $config = $this->routes[$route];
            $executor = is_string($config)
                ? $config
                : $config['executor'] ?? null;
            if ($executor) {
                return new ConsoleResourceContext([
                    'executor' => $executor,
                ]);
            }
        }


//        $e = [
//            'loc_com' => CommandClass,
//            '!serv' => 'service/name',
//        ];
        //TODO !serv


        // Если не найдено
        return new ConsoleResourceContext([
            'executor' => DefaultCommand::class,
        ]);
    }
}
