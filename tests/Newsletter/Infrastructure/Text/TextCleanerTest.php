<?php
declare(strict_types=1);

namespace App\Tests\Newsletter\Infrastructure\Text;

use App\Newsletter\Infrastructure\Text\TextCleaner;
use PHPUnit\Framework\TestCase;

/**
 * Cases lifted verbatim from:
 *   - next/lib/cleanText.spec.ts
 *   - social/linkedin/test/cleanText.spec.ts
 * in the benchmark sibling repo. Any divergence here means the legacy JS
 * cleaner and this PHP cleaner will render the same input differently.
 */
final class TextCleanerTest extends TestCase
{
    public function test_repair_mojibake_repairs_e_acute(): void
    {
        self::assertSame('Café', TextCleaner::repairMojibake('CafÃ©'));
    }

    public function test_repair_mojibake_repairs_e_diaeresis(): void
    {
        self::assertSame('Israël', TextCleaner::repairMojibake('IsraÃ«l'));
    }

    public function test_repair_mojibake_repairs_multiple_sequences(): void
    {
        self::assertSame("L'Humanité attaquée", TextCleaner::repairMojibake("L'HumanitÃ© attaquÃ©e"));
    }

    public function test_repair_mojibake_leaves_valid_utf8_untouched(): void
    {
        self::assertSame('Café déjà-vu — émoji 🌷', TextCleaner::repairMojibake('Café déjà-vu — émoji 🌷'));
    }

    public function test_repair_mojibake_leaves_plain_ascii_untouched(): void
    {
        self::assertSame('hello world', TextCleaner::repairMojibake('hello world'));
    }

    public function test_repair_mojibake_refuses_strings_with_chars_beyond_latin1(): void
    {
        self::assertSame('🌷 CafÃ©', TextCleaner::repairMojibake('🌷 CafÃ©'));
    }

    public function test_repair_mojibake_returns_empty_for_empty(): void
    {
        self::assertSame('', TextCleaner::repairMojibake(''));
    }

    public function test_clean_repairs_mojibake_before_stripping_artefacts(): void
    {
        self::assertSame('Café', TextCleaner::clean('"CafÃ©"'));
    }

    public function test_clean_decodes_x202f_nnbsp_to_space(): void
    {
        self::assertSame('connue : attaquer', TextCleaner::clean('connue\\x202f\\: attaquer'));
    }

    public function test_clean_decodes_x2026_ellipsis(): void
    {
        self::assertSame('voir aussi…', TextCleaner::clean('voir aussi\\x2026'));
    }

    public function test_clean_does_not_confuse_xa0_with_4_digit_form(): void
    {
        self::assertSame('1er mai', TextCleaner::clean('1er\\xa0\\mai'));
    }

    public function test_clean_returns_empty_for_empty(): void
    {
        self::assertSame('', TextCleaner::clean(''));
    }

    public function test_clean_strips_wrapping_straight_quotes(): void
    {
        self::assertSame('Hello', TextCleaner::clean('"Hello"'));
    }

    public function test_clean_converts_literal_backslash_n_to_real_lf(): void
    {
        self::assertSame("line one\nline two", TextCleaner::clean('line one\\nline two'));
    }

    public function test_clean_decodes_escaped_quotes_inside_body(): void
    {
        self::assertSame("L'Espagne et l'Italie", TextCleaner::clean("L\\'Espagne et l\\'Italie"));
    }

    public function test_clean_decodes_css_style_hex_to_printable_ascii(): void
    {
        self::assertSame('A/B', TextCleaner::clean('A\\2f\\B'));
    }

    public function test_clean_strips_bare_backslashes_after_other_transforms(): void
    {
        self::assertSame('foo!bar', TextCleaner::clean('foo\\!bar'));
    }

    public function test_clean_trims_surrounding_whitespace(): void
    {
        self::assertSame('hello', TextCleaner::clean('   hello   '));
    }

    public function test_clean_handles_humanite_snapshot_row(): void
    {
        // The ➡️ in the source carries a U+FE0F variation selector that
        // step 7 strips, leaving just the base U+27A1 codepoint - matching
        // the JS/Prolog canonicals.
        $input = 'TotalEnergies se réjouit de «\\xa0\\l’environnement favorable\\xa0\\» de la guerre en Iran et encaisse 5,8\\xa0\\milliards de dollars de bénéfices \\n\\n➡️ https://l.humanite.fr/FxG';
        $expected = "TotalEnergies se réjouit de « l’environnement favorable » de la guerre en Iran et encaisse 5,8 milliards de dollars de bénéfices \n\n➡ https://l.humanite.fr/FxG";

        self::assertSame($expected, TextCleaner::clean($input));
    }

    /**
     * Regression for the 2026-05-24 report where Mediapart-shaped rows reached
     * subscribers with both the wrapping straight quotes AND the literal `\n\n`
     * byline separator still present. Pins both invariants in one assertion
     * using the exact text shape the user pasted from a delivered newsletter.
     */
    public function test_clean_strips_wrapping_quotes_and_decodes_literal_lf_in_mediapart_row(): void
    {
        $input = '"Des milliers de familles de la bande de Gaza sont sans nouvelle de leurs proches, volatilisés sans laisser de traces. Certains de ces disparus forcés croupissent dans des lieux de détention en Israël. Obtenir des informations relève de la gageure.\\n\\nPar Gwenaelle Lenoir"';
        $expected = "Des milliers de familles de la bande de Gaza sont sans nouvelle de leurs proches, volatilisés sans laisser de traces. Certains de ces disparus forcés croupissent dans des lieux de détention en Israël. Obtenir des informations relève de la gageure.\n\nPar Gwenaelle Lenoir";

        $cleaned = TextCleaner::clean($input);

        self::assertSame($expected, $cleaned);
        self::assertStringNotContainsString('\\n', $cleaned);
        self::assertStringStartsNotWith('"', $cleaned);
        self::assertStringEndsNotWith('"', $cleaned);
    }
}
