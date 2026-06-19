<?php

declare(strict_types=1);

namespace Letkode\CommonBundle\Utils;

use Letkode\HelpersBundle\String\ReplaceValuesTextFromArrayHelper;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class BuilderUrlClient
{
    private const string PLACEHOLDER_PATTERN = '/#\[([^\]]+)\]#/';

    public function __construct(
        #[Autowire(env: 'APP_CLIENT_URL')]
        private string $appClientUrl,
        private ReplaceValuesTextFromArrayHelper $replaceValuesHelper,
    ) {
    }

    /**
     * @param array<string, scalar> $parameters placeholder values and/or extra query params
     */
    public function generate(string $path, array $parameters = []): string
    {
        $usedKeys = $this->extractPlaceholderKeys($path);

        $path = $this->replaceValuesHelper->handle($path, ['values' => $parameters]);

        $queryParams = array_diff_key($parameters, array_flip($usedKeys));
        if ([] !== $queryParams) {
            $path .= '?' . http_build_query($queryParams);
        }

        $separator = str_starts_with($path, '/') ? '' : '/';

        return $this->appClientUrl . $separator . $path;
    }

    /** @return list<string> */
    private function extractPlaceholderKeys(string $path): array
    {
        preg_match_all(self::PLACEHOLDER_PATTERN, $path, $matches);

        return $matches[1] ?? [];
    }
}
