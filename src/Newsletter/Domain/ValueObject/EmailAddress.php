<?php
declare(strict_types=1);

namespace App\Newsletter\Domain\ValueObject;

final class EmailAddress
{
    private function __construct(private readonly string $normalised)
    {
    }

    public static function fromString(string $raw): self
    {
        $normalised = strtolower(trim($raw));
        if ($normalised === '' || filter_var($normalised, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidEmailAddress(sprintf('not a valid email address: "%s"', $raw));
        }

        return new self($normalised);
    }

    public function value(): string
    {
        return $this->normalised;
    }

    public function unmask(): string
    {
        return $this->normalised;
    }

    public function hash(): string
    {
        return hash('sha256', $this->normalised);
    }

    public function __toString(): string
    {
        [$local, $domain] = explode('@', $this->normalised, 2);
        $first = substr($local, 0, 1);

        return sprintf('%s***@%s', $first, $domain);
    }
}
