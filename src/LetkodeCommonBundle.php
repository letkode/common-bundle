<?php

declare(strict_types=1);

namespace Letkode\CommonBundle;

use Letkode\CommonBundle\OpenApi\DocAttributeRouteDescriber;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class LetkodeCommonBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import($this->getPath() . '/config/services.yaml');

        // Optional: only when NelmioApiDocBundle is installed. Registered here rather than through
        // services.yaml because the class implements one of its interfaces, so it cannot be loaded without it.
        if (interface_exists(RouteDescriberInterface::class)) {
            $container->services()
                ->set(DocAttributeRouteDescriber::class)
                ->tag('nelmio_api_doc.route_describer', ['priority' => -500]);
        }
    }
}
