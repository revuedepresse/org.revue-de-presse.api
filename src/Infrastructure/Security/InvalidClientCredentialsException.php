<?php
declare(strict_types=1);

namespace App\Infrastructure\Security;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class InvalidClientCredentialsException extends UnauthorizedHttpException
{
    public function __construct(string $message)
    {
        parent::__construct(challenge: 'Basic realm="revue-de-presse"', message: $message);
    }
}
