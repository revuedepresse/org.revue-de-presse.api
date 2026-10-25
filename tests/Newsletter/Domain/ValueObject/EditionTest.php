<?php
declare(strict_types=1);

namespace App\Tests\Newsletter\Domain\ValueObject;

use App\Newsletter\Domain\ValueObject\Edition;
use App\Newsletter\Domain\ValueObject\InvalidEdition;
use PHPUnit\Framework\TestCase;

final class EditionTest extends TestCase
{
    public function test_a_form_asks_for_one_edition_or_both(): void
    {
        self::assertSame([Edition::FrenchMedia], Edition::listFromForm(null));
        self::assertSame([Edition::CoopMedias], Edition::listFromForm('coop-medias'));
        self::assertSame([Edition::FrenchMedia, Edition::CoopMedias], Edition::listFromForm('both'));
        self::assertSame([Edition::FrenchMedia, Edition::CoopMedias], Edition::listFromForm(['coop-medias', 'fr', 'fr']));
    }

    public function test_an_unknown_edition_is_refused(): void
    {
        $this->expectException(InvalidEdition::class);

        Edition::listFromForm(['fr', 'all']);
    }
}
