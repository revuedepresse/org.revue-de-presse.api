<?php
declare(strict_types=1);

namespace App\NewsReview\Domain\EndOfDay;

/**
 * A calendar day in Europe/Paris, and the moments its top 10 is cut off:
 * 22:00, when the nightly ranking runs, and midnight, when the day ends.
 * Times are UTC, as publications and measurements are stored.
 */
final class ParisDay
{
    private function __construct(private readonly \DateTimeImmutable $start)
    {
    }

    public static function of(string $date): self
    {
        $paris = new \DateTimeZone('Europe/Paris');
        $start = \DateTimeImmutable::createFromFormat('!Y-m-d', $date, $paris);
        if ($start === false || $start->format('Y-m-d') !== $date) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a calendar date (YYYY-MM-DD).', $date));
        }

        return new self($start);
    }

    public function date(): string
    {
        return $this->start->format('Y-m-d');
    }

    public function startsAt(): \DateTimeImmutable
    {
        return $this->utc($this->start);
    }

    public function endsAt(): \DateTimeImmutable
    {
        return $this->utc($this->start->modify('+1 day'));
    }

    /** @return array{'22:00': \DateTimeImmutable, midnight: \DateTimeImmutable} */
    public function cutOffs(): array
    {
        return [
            '22:00' => $this->utc($this->start->setTime(22, 0)),
            'midnight' => $this->endsAt(),
        ];
    }

    private function utc(\DateTimeImmutable $at): \DateTimeImmutable
    {
        return $at->setTimezone(new \DateTimeZone('UTC'));
    }
}
