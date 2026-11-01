<?php

declare(strict_types=1);

namespace Letkode\CommonBundle\Tests\OpenApi;

use Letkode\CommonBundle\OpenApi\DocAttributeRouteDescriber;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use OpenApi\Annotations as OA;
use OpenApi\Attributes as OAT;
use OpenApi\Generator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Route;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
#[OAT\Get(path: '/api/items', summary: 'List items', description: 'Paginated list')]
#[OAT\Post(path: '/api/items', summary: 'Create item')]
#[OAT\Get(path: '/api/items/{id}', summary: 'Show item')]
final class DocItemsEndpoints
{
}

#[\Attribute(\Attribute::TARGET_CLASS)]
#[OAT\Get(
    path: '/api/merge',
    summary: 'From the Doc',
    tags: ['Items', 'Extra'],
    parameters: [
        new OAT\Parameter(name: 'page', in: 'query', description: 'from the Doc'),
        new OAT\Parameter(name: 'q', in: 'query'),
    ],
    responses: [new OAT\Response(response: 200, description: 'from the Doc')],
)]
final class DocMerge
{
}

#[DocMerge]
final class MergeController
{
    public function index(): void
    {
    }
}

#[\Attribute(\Attribute::TARGET_METHOD)]
#[OAT\Get(summary: 'Pathless operation')]
final class DocPathless
{
}

#[\Attribute(\Attribute::TARGET_CLASS)]
final class PlainMarker
{
}

#[DocItemsEndpoints]
#[PlainMarker]
#[OAT\Tag(name: 'Items')]
final class ClassLevelController
{
    public function index(): void
    {
    }
}

final class MethodLevelController
{
    #[DocPathless]
    public function show(): void
    {
    }

    public function undocumented(): void
    {
    }
}

final class DocAttributeRouteDescriberTest extends TestCase
{
    public function testAppliesTheOperationThatMatchesTheRouteMethodAndPath(): void
    {
        $api = new OA\OpenApi([]);

        $this->describe($api, new Route('/api/items', methods: ['GET']), ClassLevelController::class, 'index');

        $operation = Util::getOperation(Util::getPath($api, '/api/items'), 'get');
        self::assertSame('List items', $operation->summary);
        self::assertSame('Paginated list', $operation->description);
    }

    public function testOnlyTheOperationOfTheRouteMethodIsApplied(): void
    {
        $api = new OA\OpenApi([]);

        $this->describe($api, new Route('/api/items', methods: ['POST']), ClassLevelController::class, 'index');

        $path = Util::getPath($api, '/api/items');
        self::assertSame('Create item', Util::getOperation($path, 'post')->summary);
        self::assertSame(Generator::UNDEFINED, Util::getOperation($path, 'get')->summary);
    }

    public function testAnOperationDeclaredForAnotherPathIsIgnored(): void
    {
        $api = new OA\OpenApi([]);

        $this->describe($api, new Route('/api/items/{uuid}', methods: ['GET']), ClassLevelController::class, 'index');

        $operation = Util::getOperation(Util::getPath($api, '/api/items/{uuid}'), 'get');
        self::assertSame(Generator::UNDEFINED, $operation->summary);
    }

    public function testAnOperationWithoutPathAppliesToTheMethodRoute(): void
    {
        $api = new OA\OpenApi([]);

        $this->describe($api, new Route('/api/anything', methods: ['GET']), MethodLevelController::class, 'show');

        $operation = Util::getOperation(Util::getPath($api, '/api/anything'), 'get');
        self::assertSame('Pathless operation', $operation->summary);
    }

    public function testTheFormatSuffixOfTheRoutePathIsIgnoredWhenMatching(): void
    {
        $api = new OA\OpenApi([]);

        $this->describe($api, new Route('/api/items.{_format}', methods: ['GET']), ClassLevelController::class, 'index');

        $operation = Util::getOperation(Util::getPath($api, '/api/items'), 'get');
        self::assertSame('List items', $operation->summary);
    }

    public function testControllersWithoutADocAttributeAreLeftUntouched(): void
    {
        $api = new OA\OpenApi([]);

        $this->describe($api, new Route('/api/plain', methods: ['GET']), MethodLevelController::class, 'undocumented');

        self::assertSame(Generator::UNDEFINED, $api->paths);
    }

    public function testRealOpenApiAttributesAndPlainAttributesAreNotTreatedAsDocClasses(): void
    {
        $api = new OA\OpenApi([]);

        // ClassLevelController also carries #[OAT\Tag] and #[PlainMarker]; neither may add an operation.
        $this->describe($api, new Route('/api/items', methods: ['PUT']), ClassLevelController::class, 'index');

        $path = Util::getPath($api, '/api/items');
        self::assertSame(Generator::UNDEFINED, Util::getOperation($path, 'put')->summary);
    }

    public function testTheDocWinsOverWhatNelmioAlreadyBuiltWithoutReportingAConflict(): void
    {
        $api = new OA\OpenApi([]);
        $operation = Util::getOperation(Util::getPath($api, '/api/merge'), 'get');
        $operation->summary = 'Generated';
        $operation->tags = ['Items'];
        $operation->parameters = [
            new OA\Parameter(['name' => 'page', 'in' => 'query', 'description' => 'generated']),
            new OA\Parameter(['name' => 'per_page', 'in' => 'query']),
        ];
        $operation->responses = [new OA\Response(['response' => 'default', 'description' => 'generated'])];

        $this->describe($api, new Route('/api/merge', methods: ['GET']), MergeController::class, 'index');

        self::assertSame('From the Doc', $operation->summary);
        self::assertSame(['Items', 'Extra'], $operation->tags);
        self::assertSame(['query:page', 'query:per_page', 'query:q'], array_map(static fn (OA\Parameter $p): string => $p->in . ':' . $p->name, $operation->parameters));
        self::assertSame('from the Doc', $operation->parameters[0]->description);
        self::assertSame(['default', 200], array_map(static fn (OA\Response $r): int|string => $r->response, $operation->responses));
    }

    public function testTheGlobalSwaggerContextIsResetAfterwards(): void
    {
        $this->describe(new OA\OpenApi([]), new Route('/api/items', methods: ['GET']), ClassLevelController::class, 'index');

        self::assertNull(Generator::$context);
    }

    /**
     * @param class-string $controller
     */
    private function describe(OA\OpenApi $api, Route $route, string $controller, string $method): void
    {
        new DocAttributeRouteDescriber()->describe($api, $route, new \ReflectionMethod($controller, $method));
    }
}
