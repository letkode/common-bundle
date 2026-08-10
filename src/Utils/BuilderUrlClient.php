<?php

declare(strict_types=1);

namespace Letkode\CommonBundle\Utils;

use Letkode\HelpersBundle\String\ReplaceValuesTextFromArrayHelper;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class BuilderUrlClient
{
    private const string PLACEHOLDER_PATTERN = '/#\[([^\]]+)\]#/';

    public function __construct(
        #[Autowire(env: 'APP_CLIENT_URL_PROTOCOL')]
        private string $protocol,
        #[Autowire(env: 'APP_CLIENT_URL_DOMAIN')]
        private string $domain,
        #[Autowire(env: 'APP_CLIENT_URL_PORT')]
        private string $port,
        private ReplaceValuesTextFromArrayHelper $replaceValuesHelper,
    ) {
    }

    /**
     * @param array<string, scalar> $parameters placeholder values and/or extra query params
     * @param string|null           $subdomain  prepended to the domain (e.g. tenant slug, "hub"); null builds the bare apex URL
     */
    public function generate(string $path, array $parameters = [], string|null $subdomain = null): string
    {
        $usedKeys = $this->extractPlaceholderKeys($path);

        $path = $this->replaceValuesHelper->handle($path, ['values' => $parameters]);

        $queryParams = array_diff_key($parameters, array_flip($usedKeys));
        if ([] !== $queryParams) {
            $path .= '?' . http_build_query($queryParams);
        }

        $separator = str_starts_with($path, '/') ? '' : '/';

        return $this->buildBaseUrl($subdomain) . $separator . $path;
    }

    private function buildBaseUrl(string|null $subdomain): string
    {
        $host = (null !== $subdomain ? $subdomain . '.' : '') . $this->domain;

        if ('' !== $this->port) {
            $host .= ':' . $this->port;
        }

        return $this->protocol . '://' . $host;
    }

    /** @return list<string> */
    private function extractPlaceholderKeys(string $path): array
    {
        preg_match_all(self::PLACEHOLDER_PATTERN, $path, $matches);

        return $matches[1];
    }
}
