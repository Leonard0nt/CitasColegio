<?php

function normalize_csv_cell_value(string $value): string
{
    $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
    $value = str_replace("\xc2\xa0", ' ', (string) $value);
    $value = repair_text_encoding($value);

    if (function_exists('mb_check_encoding') && !mb_check_encoding($value, 'UTF-8')) {
        $converted = convert_to_utf8($value, csv_supported_encodings());
        $value = $converted ?? $value;
    }

    return trim(preg_replace('/\s+/u', ' ', $value));
}

function repair_text_encoding(string $value): string
{
    $value = repair_utf8_mojibake($value);
    $value = repair_macintosh_misdecode($value);

    return $value;
}

function repair_utf8_mojibake(string $value): string
{
    if ($value === '' || preg_match('/(?:Ã|Â|â|ï¿½|�)/u', $value) !== 1) {
        return $value;
    }

    if (!function_exists('mb_convert_encoding') || !function_exists('mb_check_encoding')) {
        return $value;
    }

    $candidate = @mb_convert_encoding($value, 'Windows-1252', 'UTF-8');

    if ($candidate === false || !mb_check_encoding($candidate, 'UTF-8')) {
        return $value;
    }

    return text_encoding_score($candidate) < text_encoding_score($value) ? $candidate : $value;
}

function repair_macintosh_misdecode(string $value): string
{
    if ($value === '' || preg_match('/[‡Ž’—œ–\x{0087}\x{008E}\x{0092}\x{0097}\x{009C}\x{0096}]/u', $value) !== 1) {
        return $value;
    }

    $map = [
        '‡' => 'á',
        'Ž' => 'é',
        '’' => 'í',
        '—' => 'ó',
        'œ' => 'ú',
        '–' => 'ñ',
        "\u{0087}" => 'á',
        "\u{008E}" => 'é',
        "\u{0092}" => 'í',
        "\u{0097}" => 'ó',
        "\u{009C}" => 'ú',
        "\u{0096}" => 'ñ',
    ];
    $candidate = strtr($value, $map);

    return text_encoding_score($candidate) < text_encoding_score($value) ? $candidate : $value;
}

function mojibake_score(string $value): int
{
    return text_encoding_score($value);
}

function text_encoding_score(string $value): int
{
    $score = 0;

    foreach (['Ã' => 3, 'Â' => 2, 'â' => 3, 'ï¿½' => 5, '�' => 5] as $fragment => $weight) {
        $score += substr_count($value, $fragment) * $weight;
    }

    if (preg_match_all('/[‡Ž’—œ–]/u', $value, $matches) > 0) {
        $score += count($matches[0]) * 4;
    }

    if (preg_match_all('/[\x{0080}-\x{009F}]/u', $value, $matches) > 0) {
        $score += count($matches[0]) * 6;
    }

    if (preg_match_all('/[áéíóúÁÉÍÓÚñÑüÜ]/u', $value, $matches) > 0) {
        $score -= count($matches[0]);
    }

    return $score;
}

function csv_supported_encodings(): array
{
    return ['Windows-1252', 'ISO-8859-1', 'ISO-8859-15', 'Macintosh'];
}

function convert_to_utf8(string $contents, array $encodings): ?string
{
    $best = null;
    $bestScore = PHP_INT_MAX;

    foreach ($encodings as $encoding) {
        $converted = convert_encoding_to_utf8($contents, $encoding);

        if ($converted === null || (function_exists('mb_check_encoding') && !mb_check_encoding($converted, 'UTF-8'))) {
            continue;
        }

        $score = text_encoding_score($converted);
        if ($score < $bestScore) {
            $best = $converted;
            $bestScore = $score;
        }
    }

    return $best;
}

function convert_encoding_to_utf8(string $contents, string $encoding): ?string
{
    if (function_exists('mb_convert_encoding')) {
        try {
            $converted = @mb_convert_encoding($contents, 'UTF-8', $encoding);
        } catch (ValueError $error) {
            $converted = false;
        }

        if ($converted !== false) {
            return $converted;
        }
    }

    if (function_exists('iconv')) {
        $converted = @iconv($encoding, 'UTF-8//IGNORE', $contents);

        if ($converted !== false) {
            return $converted;
        }
    }

    return null;
}

function normalize_file_encoding(string $contents): string
{
    if (function_exists('mb_check_encoding') && mb_check_encoding($contents, 'UTF-8')) {
        return repair_utf8_mojibake($contents);
    }

    $encodings = csv_supported_encodings();

    if (function_exists('mb_detect_encoding')) {
        $detectableEncodings = array_filter(array_merge(['UTF-8'], $encodings), static fn($encoding) => $encoding !== 'Macintosh');
        $encoding = mb_detect_encoding($contents, $detectableEncodings, true);

        if (is_string($encoding) && $encoding !== 'UTF-8') {
            array_unshift($encodings, $encoding);
            $encodings = array_values(array_unique($encodings));
        }
    }

    return convert_to_utf8($contents, $encodings) ?? $contents;
}
