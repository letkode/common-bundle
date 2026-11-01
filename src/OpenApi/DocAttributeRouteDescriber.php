<?php

declare(strict_types=1);

namespace Letkode\CommonBundle\OpenApi;

use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberInterface;
use OpenApi\Analysers\AttributeAnnotationFactory;
use OpenApi\Annotations as OA;
use OpenApi\Generator;
use Symfony\Component\Routing\Route;

/**
 * Applies the OpenAPI attributes of a "Doc" class referenced by a controller attribute.
 *
 * NelmioApiDocBundle only reads `#[OA\*]` attributes declared on the controller class or method
 * itself. An application that keeps its documentation apart from the controller declares a plain
 * attribute class that carries the operations,
 *
 *     #[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
 *     #[OA\Get(path: '/api/companies', summary: 'List companies')]
 *     final class DocCompaniesEndpoints {}
 *
 * and applies it to the controller with `#[DocCompaniesEndpoints]`. Nelmio instantiates that
 * attribute and ignores it, so none of its operations reach the generated specification. This
 * describer reads them: for every attribute on the controller class or method whose own class
 * carries `OA\*` attributes, each operation that targets the route (same HTTP method, and same path
 * when the operation declares one) is merged into the route's operation, exactly as Nelmio does
 * with an `#[OA\*]` attribute placed directly on the controller.
 *
 * Only operations (`OA\Get`, `OA\Post`, ...) are applied; a root `OA\Response`, `OA\Parameter` or
 * `OA\RequestBody` on a Doc class has no operation to belong to and is ignored.
 */
final class DocAttributeRouteDescriber implements RouteDescriberInterface
{
    public function describe(OA\OpenApi $api, Route $route, \ReflectionMethod $reflectionMethod): void
    {
        $classReflector = $this->resolveControllerClass($route, $reflectionMethod);

        $carriers = [
            ...$this->findCarriers($classReflector),
            ...$this->findCarriers($reflectionMethod),
        ];
        if ([] === $carriers) {
            return;
        }

        $pathValue = $this->normalizePath($route->getPath());
        $methods = $this->supportedHttpMethods($route);
        $path = Util::getPath($api, $pathValue);

        $context = Util::createContext(['nested' => $path], $path->_context);
        $context->namespace = $classReflector->getNamespaceName();
        $context->class = $classReflector->getShortName();
        $context->method = $reflectionMethod->name;
        $context->filename = $reflectionMethod->getFileName() ?: null;

        $factory = new AttributeAnnotationFactory(true);

        try {
            // swagger-php reads the global context while it instantiates attributes.
            Generator::$context = $context;

            foreach ($carriers as $carrier) {
                foreach ($factory->build($carrier, $context) as $annotation) {
                    if (!$annotation instanceof OA\Operation || !\in_array($annotation->method, $methods, true)) {
                        continue;
                    }
                    if (Generator::UNDEFINED !== $annotation->path && $annotation->path !== $pathValue) {
                        continue;
                    }

                    $this->merge(Util::getOperation($path, $annotation->method), $annotation);
                }
            }
        } finally {
            Generator::$context = null;
        }
    }

    /**
     * Merges a Doc operation into the one Nelmio already built for the route.
     *
     * `AbstractAnnotation::mergeProperties()` cannot be used: it never overwrites a defined property and
     * reports every difference as an error, which Nelmio turns into an exception. Here the Doc, being the
     * explicit documentation, takes precedence: `tags` are united, `parameters` and `responses` are matched
     * by (name, in) and by status code, and any other property the Doc defines replaces the generated one.
     */
    private function merge(OA\Operation $target, OA\Operation $doc): void
    {
        foreach (get_object_vars($doc) as $property => $value) {
            if (str_starts_with($property, '_') || 'path' === $property || 'method' === $property || Generator::isDefault($value)) {
                continue;
            }

            $current = $target->{$property};
            if (Generator::isDefault($current) || !\is_array($current) || !\is_array($value)) {
                $target->{$property} = $value;

                continue;
            }

            $target->{$property} = match ($property) {
                'tags' => array_values(array_unique(array_filter([...$current, ...$value], \is_string(...)))),
                'parameters' => $this->mergeByKey($current, $value, static function (mixed $parameter): string {
                    \assert($parameter instanceof OA\Parameter);

                    return $parameter->in . ':' . $parameter->name;
                }),
                'responses' => $this->mergeByKey($current, $value, static function (mixed $response): string {
                    \assert($response instanceof OA\Response);

                    return (string) $response->response;
                }),
                default => $value,
            };
        }
    }

    /**
     * @param array<mixed>            $current
     * @param array<mixed>            $incoming
     * @param \Closure(mixed): string $keyOf
     *
     * @return list<mixed>
     */
    private function mergeByKey(array $current, array $incoming, \Closure $keyOf): array
    {
        $merged = [];
        foreach ($current as $item) {
            $merged[$keyOf($item)] = $item;
        }
        foreach ($incoming as $item) {
            $merged[$keyOf($item)] = $item;
        }

        return array_values($merged);
    }

    /**
     * The attribute classes applied to $reflector that carry `OA\*` attributes themselves,
     * as reflections ready for swagger-php. A real `OA\*` attribute is not one: Nelmio reads those.
     *
     * @param \ReflectionClass<object>|\ReflectionMethod $reflector
     *
     * @return list<\ReflectionClass<object>>
     */
    private function findCarriers(\ReflectionClass|\ReflectionMethod $reflector): array
    {
        $carriers = [];

        foreach ($reflector->getAttributes() as $attribute) {
            $name = $attribute->getName();
            if (!class_exists($name) || is_subclass_of($name, OA\AbstractAnnotation::class)) {
                continue;
            }

            $carrier = new \ReflectionClass($name);
            if ([] !== $carrier->getAttributes(OA\AbstractAnnotation::class, \ReflectionAttribute::IS_INSTANCEOF)) {
                $carriers[$name] = $carrier;
            }
        }

        return array_values($carriers);
    }

    /**
     * @return \ReflectionClass<object>
     */
    private function resolveControllerClass(Route $route, \ReflectionMethod $reflectionMethod): \ReflectionClass
    {
        $controller = $route->getDefault('_controller');

        if (\is_string($controller) && false !== $position = strpos($controller, '::')) {
            $class = substr($controller, 0, $position);

            // The route may name a subclass of the declaring class; fall back when it cannot be loaded.
            if (class_exists($class)) {
                return new \ReflectionClass($class);
            }
        }

        return $reflectionMethod->getDeclaringClass();
    }

    /**
     * @return list<string> lower-case HTTP methods; every operation when the route allows any
     */
    private function supportedHttpMethods(Route $route): array
    {
        $methods = array_map('strtolower', $route->getMethods());

        return [] === $methods ? Util::OPERATIONS : array_values(array_intersect($methods, Util::OPERATIONS));
    }

    private function normalizePath(string $path): string
    {
        return str_ends_with($path, '.{_format}') ? substr($path, 0, -10) : $path;
    }
}
