<?php
declare(strict_types=1);

namespace App\Newsletter\Domain\ValueObject;

/**
 * Which daily newsletter a subscription is to. Each edition is the top 10 of
 * one publisher list, so the values are the lists' (see PublisherList).
 *
 * An address subscribes to each edition separately: one row, one confirmation
 * and one unsubscribe link per edition.
 */
enum Edition: string
{
    case FrenchMedia = 'fr';
    case CoopMedias = 'coop-medias';
    // One letter, the top 10 of both lists together.
    case FrenchAndCoopMedias = 'fr-coop-medias';

    public static function fromForm(mixed $value): self
    {
        if ($value === null || $value === '') {
            return self::FrenchMedia;
        }

        if (!is_string($value)) {
            throw new InvalidEdition('(non-string)');
        }

        return self::tryFrom($value) ?? throw new InvalidEdition($value);
    }

    /**
     * What a sign-up form asks for: one edition, "both", or a list of them.
     *
     * @return list<self> in declaration order, each once
     */
    public static function listFromForm(mixed $value): array
    {
        // Two letters, one per list.
        if ($value === 'both') {
            return [self::FrenchMedia, self::CoopMedias];
        }

        $asked = array_map(self::fromForm(...), is_array($value) ? array_values($value) : [$value]);

        return array_values(array_filter(self::cases(), static fn(self $edition): bool => in_array($edition, $asked, true)));
    }

    /** "Vous êtes abonné·e à …" */
    public function newsletterName(): string
    {
        return match ($this) {
            self::FrenchMedia => "la lettre d'information des médias français",
            self::CoopMedias => "la lettre d'information de Coop Médias",
            self::FrenchAndCoopMedias => "la lettre d'information des médias français et de Coop Médias",
        };
    }

    /** "Top 10 des publications … les plus relayées sur Bluesky" */
    public function publishers(): string
    {
        return match ($this) {
            self::FrenchMedia => 'des médias français',
            self::CoopMedias => 'des médias de Coop Médias',
            self::FrenchAndCoopMedias => 'des médias français et de Coop Médias',
        };
    }

    public function subject(string $dateFr): string
    {
        return match ($this) {
            self::FrenchMedia => sprintf('Revue de presse — %s', $dateFr),
            self::CoopMedias => sprintf('Revue de presse Coop Médias — %s', $dateFr),
            self::FrenchAndCoopMedias => sprintf('Revue de presse, médias français et Coop Médias — %s', $dateFr),
        };
    }
}
