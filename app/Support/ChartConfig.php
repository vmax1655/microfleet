<?php

namespace App\Support;

/**
 * Serialises a PHP chart config to a JS object literal.
 *
 * Chart.js needs real functions for tick/tooltip formatting, which JSON cannot
 * carry. Any string value prefixed with "@js:" is emitted as a raw expression:
 *
 *     'callback' => '@js:(v) => window.LedgerFormat.pesoShort(v)'
 */
class ChartConfig
{
    public static function js(array $config): string
    {
        $functions = [];

        array_walk_recursive($config, function (&$value) use (&$functions) {
            if (is_string($value) && str_starts_with($value, '@js:')) {
                $token = '__LEDGER_FN_'.count($functions).'__';
                $functions[$token] = substr($value, 4);
                $value = $token;
            }
        });

        $json = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        foreach ($functions as $token => $expression) {
            $json = str_replace('"'.$token.'"', $expression, $json);
        }

        return $json;
    }

    /** Common y-axis money formatting shared by most charts. */
    public static function pesoAxis(): array
    {
        return [
            'beginAtZero' => true,
            'ticks' => ['callback' => '@js:(v) => window.LedgerFormat.pesoShort(v)'],
            'grid' => ['color' => '#F0F4F2'],
            'border' => ['display' => false],
        ];
    }

    public static function pesoTooltip(): array
    {
        return [
            'callbacks' => [
                'label' => '@js:(ctx) => " " + ctx.dataset.label + ": " + window.LedgerFormat.peso(ctx.parsed.y ?? ctx.parsed)',
            ],
        ];
    }

    public static function percentAxis(?float $min = null, ?float $max = null): array
    {
        return array_filter([
            'min' => $min,
            'max' => $max,
            'ticks' => ['callback' => '@js:(v) => v + "%"'],
            'grid' => ['color' => '#F0F4F2'],
            'border' => ['display' => false],
        ], fn ($v) => $v !== null);
    }
}
