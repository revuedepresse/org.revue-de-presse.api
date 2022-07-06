<?php
declare(strict_types=1);

namespace App\Membership\Domain\Exception;

use App\Membership\Domain\Model\MemberInterface;
use Exception;
use Throwable;

class MembershipException extends Exception
{
    private ?MemberInterface $exceptionalMember;

    public function exceptionalMember(): ?MemberInterface
    {
        return $this->exceptionalMember;
    }

    public function __construct(
        string $message = '',
        int $code = 0,
        Throwable $previous = null,
        ?MemberInterface $exceptionMember = null
    )
    {
        parent::__construct($message, $code, $previous);

        $this->exceptionalMember = $exceptionMember;
    }

    public static function throws(
        string $message,
        int $errorCode,
        ?MemberInterface $exceptionalMember = null,
        Throwable $previous = null
    ): void
    {
        throw new self($message, $errorCode, $previous, $exceptionalMember);
    }
}