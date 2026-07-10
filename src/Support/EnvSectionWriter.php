<?php

namespace Susheelbhai\Larapay\Support;

class EnvSectionWriter
{
    /**
     * @param  array<string, string>  $values
     * @param  list<string>  $insertAnchors
     */
    public static function sync(
        string $content,
        string $sectionHeader,
        array $values,
        array $insertAnchors = [],
    ): string {
        foreach ($values as $key => $value) {
            if (self::keyExists($content, $key)) {
                $content = self::replaceLine($content, $key, $value);
            }
        }

        $missing = [];

        foreach ($values as $key => $value) {
            if (! self::keyExists($content, $key)) {
                $missing[$key] = $value;
            }
        }

        if ($missing === []) {
            return $content;
        }

        $sectionKeys = array_keys($values);

        if (self::sectionExists($content, $sectionHeader)) {
            return self::appendToSection($content, $sectionHeader, $missing, $sectionKeys);
        }

        return self::insertSection($content, $sectionHeader, $missing, $insertAnchors);
    }

    /**
     * @return list<string>
     */
    public static function defaultInsertAnchors(): array
    {
        return [
            'LARASHIP_ORDER_MODEL',
            'FEATURE_DASHBOARD',
            'FEATURE_COMMON',
            'OTP_REVEAL_MISSING_ACCOUNT',
            'FACEBOOK_PIXEL_ID',
            'GOOGLE_CLIENT_ID',
            'INERTIA_SSR_ENABLED',
            'VITE_APP_NAME',
        ];
    }

    /**
     * @param  array<string, string>  $lines
     * @param  list<string>  $insertAnchors
     */
    private static function insertSection(
        string $content,
        string $sectionHeader,
        array $lines,
        array $insertAnchors,
    ): string {
        $anchors = $insertAnchors !== [] ? $insertAnchors : self::defaultInsertAnchors();

        foreach ($anchors as $anchor) {
            $insertAfter = self::findLineEnd($content, $anchor);

            if ($insertAfter !== null) {
                $block = "\n{$sectionHeader}\n".self::formatLines($lines);

                return substr($content, 0, $insertAfter).$block.substr($content, $insertAfter);
            }
        }

        $separator = str_ends_with(rtrim($content), "\n") ? '' : "\n";

        return rtrim($content).$separator."\n{$sectionHeader}\n".self::formatLines($lines);
    }

    /**
     * @param  array<string, string>  $lines
     * @param  list<string>  $sectionKeys
     */
    private static function appendToSection(
        string $content,
        string $sectionHeader,
        array $lines,
        array $sectionKeys,
    ): string {
        $headerPos = strpos($content, $sectionHeader);

        if ($headerPos === false) {
            return self::insertSection($content, $sectionHeader, $lines, []);
        }

        $insertOffset = self::findSectionInsertOffset($content, $sectionHeader, $sectionKeys);

        return substr($content, 0, $insertOffset).self::formatLines($lines).substr($content, $insertOffset);
    }

    /**
     * @param  list<string>  $sectionKeys
     */
    private static function findSectionInsertOffset(string $content, string $sectionHeader, array $sectionKeys): int
    {
        $sectionStart = strpos($content, $sectionHeader) + strlen($sectionHeader);
        $sectionBody = substr($content, $sectionStart);
        $pattern = self::sectionKeyPattern($sectionKeys);

        if (preg_match_all($pattern, $sectionBody, $matches, PREG_OFFSET_CAPTURE)) {
            $lastMatch = end($matches[0]);
            $lineEnd = strpos($sectionBody, "\n", $lastMatch[1]);

            if ($lineEnd === false) {
                return strlen($content);
            }

            return $sectionStart + $lineEnd + 1;
        }

        if (str_starts_with($sectionBody, "\n")) {
            return $sectionStart + 1;
        }

        return $sectionStart;
    }

    /**
     * @param  list<string>  $sectionKeys
     */
    private static function sectionKeyPattern(array $sectionKeys): string
    {
        if ($sectionKeys === []) {
            return '/^[A-Z][A-Z0-9_]*=[^\n]*/m';
        }

        $escaped = array_map(fn (string $key): string => preg_quote($key, '/'), $sectionKeys);

        return '/^('.implode('|', $escaped).')=[^\n]*/m';
    }

    private static function sectionExists(string $content, string $sectionHeader): bool
    {
        return str_contains($content, $sectionHeader);
    }

    private static function keyExists(string $content, string $key): bool
    {
        return preg_match('/^'.preg_quote($key, '/').'=/m', $content) === 1;
    }

    private static function replaceLine(string $content, string $key, string $value): string
    {
        $line = "{$key}={$value}";

        return (string) preg_replace('/^'.preg_quote($key, '/').'=.*/m', $line, $content);
    }

    private static function findLineEnd(string $content, string $key): ?int
    {
        if (! preg_match('/^'.preg_quote($key, '/').'=.*/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $lineStart = $matches[0][1];
        $lineEnd = strpos($content, "\n", $lineStart);

        if ($lineEnd === false) {
            return strlen($content);
        }

        return $lineEnd + 1;
    }

    /**
     * @param  array<string, string>  $lines
     */
    private static function formatLines(array $lines): string
    {
        $block = '';

        foreach ($lines as $key => $value) {
            $block .= "{$key}={$value}\n";
        }

        return $block;
    }
}
