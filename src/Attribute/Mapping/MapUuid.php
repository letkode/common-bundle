<?php

declare(strict_types=1);

namespace Letkode\CommonBundle\Attribute\Mapping;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
final readonly class MapUuid
{
    public function __construct(
        public string $invalidKey = 'uuid.invalid',
        public string $domain = 'exceptions',
    ) {
    }
}
