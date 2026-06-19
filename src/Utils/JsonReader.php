<?php

declare(strict_types=1);

namespace Letkode\CommonBundle\Utils;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class JsonReader
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    /**
     * @return array<mixed>|null null when the file does not exist
     *
     * @throws \RuntimeException on read failure
     * @throws \JsonException    on invalid JSON
     */
    public function read(string $filename, string|null $folder = null): array|null
    {
        $filePath = implode('/', array_filter([$this->projectDir, $folder, $filename]));

        if (!file_exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);

        if (false === $content) {
            throw new \RuntimeException(\sprintf('Could not read file "%s".', $filePath));
        }

        return json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
    }
}
