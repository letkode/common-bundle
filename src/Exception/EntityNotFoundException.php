<?php

declare(strict_types=1);

namespace Letkode\CommonBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

final class EntityNotFoundException extends \RuntimeException implements HttpStatusExceptionInterface
{
    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
