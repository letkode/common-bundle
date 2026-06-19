<?php

declare(strict_types=1);

namespace Letkode\CommonBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

final class TooManyRequestsException extends \RuntimeException implements HttpStatusExceptionInterface
{
    public function getStatusCode(): int
    {
        return Response::HTTP_TOO_MANY_REQUESTS;
    }
}
