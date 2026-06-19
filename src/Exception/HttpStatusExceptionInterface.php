<?php

declare(strict_types=1);

namespace Letkode\CommonBundle\Exception;

interface HttpStatusExceptionInterface
{
    public function getStatusCode(): int;
}
