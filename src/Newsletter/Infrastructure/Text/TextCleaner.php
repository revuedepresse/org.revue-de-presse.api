<?php
declare(strict_types=1);

namespace App\Newsletter\Infrastructure\Text;

/**
 * Strips upstream encoding artefacts from raw status text before rendering.
 *
 * Faithful PHP port of next/lib/cleanText.ts in the benchmark sibling repo
 * (canonical) and src/clean_text.pl in org.revue-de-presse.bsky. If you
 * change behaviour here, mirror it in the JS/Prolog implementations and
 * re-run all three test suites so legacy and fresh rows render identically
 * for the same input.
 */
final class TextCleaner
{
    public static function clean(string $text): string
    {
        if ($text === '') {
            return '';
        }

        $out = self::repairMojibake($text);

        if (\strlen($out) >= 2 && str_starts_with($out, '"') && str_ends_with($out, '"')) {
            $out = substr($out, 1, -1);
        }

        $out = str_replace('\\n', "\n", $out);

        $out = str_replace(["\\'", '\\"'], ["'", '"'], $out);

        $out = preg_replace_callback('/\\\\x([0-9a-fA-F]{4})\\\\?/', static function (array $m): string {
            $code = (int) hexdec($m[1]);
            if ($code === 0xA0 || $code === 0x2007 || $code === 0x202F) {
                return ' ';
            }
            if ($code < 0x20 || ($code >= 0x7F && $code < 0xA0) || ($code >= 0xD800 && $code <= 0xDFFF)) {
                return '';
            }
            return mb_chr($code, 'UTF-8');
        }, $out);

        $out = preg_replace_callback('/\\\\x([0-9a-fA-F]{2})\\\\?/', static function (array $m): string {
            $code = (int) hexdec($m[1]);
            if ($code === 0xA0) {
                return ' ';
            }
            if ($code >= 0x20 && $code < 0x7F) {
                return chr($code);
            }
            return '';
        }, $out);

        $out = preg_replace_callback('/\\\\([0-9a-fA-F]{2})\\\\?/', static function (array $m): string {
            $code = (int) hexdec($m[1]);
            if ($code >= 0x20 && $code < 0x7F) {
                return chr($code);
            }
            return '';
        }, $out);

        $out = preg_replace('/\\\\[0-9]{4}\\\\?/', '', $out);

        $out = preg_replace('/[\x{200B}-\x{200D}\x{2060}\x{FE0E}\x{FE0F}]/u', '', $out);

        $out = str_replace('\\', '', $out);

        $out = preg_replace('/[ \t]{2,}/', ' ', $out);

        return trim($out);
    }

    public static function repairMojibake(string $text): string
    {
        if ($text === '') {
            return '';
        }
        if (!preg_match('/[\x{00C2}\x{00C3}][\x{0080}-\x{00BF}]/u', $text)) {
            return $text;
        }
        $chars = mb_str_split($text, 1, 'UTF-8');
        $bytes = '';
        foreach ($chars as $ch) {
            $code = mb_ord($ch, 'UTF-8');
            if ($code > 0xFF) {
                return $text;
            }
            $bytes .= chr($code);
        }
        if (!mb_check_encoding($bytes, 'UTF-8')) {
            return $text;
        }
        return $bytes;
    }
}
