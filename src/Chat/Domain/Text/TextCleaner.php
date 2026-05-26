<?php
declare(strict_types=1);

namespace App\Chat\Domain\Text;

/**
 * Pre-embedding hygiene: decode literal \uXXXX escapes, NFC-normalise,
 * strip control characters, collapse whitespace runs, trim. Idempotent.
 */
final class TextCleaner
{
    public function clean(string $raw): string
    {
        // 1. Decode any literal \uXXXX sequences left in upstream snapshots.
        $unescaped = preg_replace_callback(
            '/\\\\u([0-9a-fA-F]{4})/',
            static fn (array $match): string => mb_chr((int) hexdec($match[1]), 'UTF-8'),
            $raw,
        ) ?? $raw;

        // 2. NFC normalise so combining marks embed identically to precomposed forms.
        $normalised = class_exists(\Normalizer::class)
            ? (\Normalizer::normalize($unescaped, \Normalizer::FORM_C) ?: $unescaped)
            : $unescaped;

        // 3. Strip C0/C1 control characters except TAB; collapses newlines.
        $stripped = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', ' ', $normalised) ?? $normalised;

        // 4. Collapse runs of whitespace (incl. NBSP \xA0) to single spaces.
        $collapsed = preg_replace('/[\s\x{00A0}]+/u', ' ', $stripped) ?? $stripped;

        // 5. Trim.
        return trim($collapsed);
    }
}
