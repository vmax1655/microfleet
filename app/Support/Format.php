<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Presentation helpers shared by every Blade view.
 *
 * Money is ALWAYS rendered through peso() so the format (₱1,234,567.89) and
 * the tabular-nums / right-alignment rules stay consistent across the app.
 */
class Format
{
    /** Chart series colors, in the order they must be consumed. */
    public const CHART_COLORS = ['#0F7A68', '#E8940F', '#1570EF', '#7FCFBE', '#B42318', '#5A6B64'];

    /** Portfolio-at-risk aging ramp: Current, 1-30, 31-60, 61-90, 90+. */
    public const PAR_RAMP = ['#DBF3ED', '#FAC978', '#F5AC3D', '#E8940F', '#B42318'];

    /** ₱1,234,567.89 */
    public static function peso(float|int|null $amount, int $decimals = 2): string
    {
        return '₱'.number_format((float) $amount, $decimals);
    }

    /** ₱1.24M — for tight KPI tiles only. */
    public static function pesoCompact(float|int|null $amount): string
    {
        $amount = (float) $amount;

        return match (true) {
            abs($amount) >= 1_000_000 => '₱'.number_format($amount / 1_000_000, 2).'M',
            abs($amount) >= 1_000 => '₱'.number_format($amount / 1_000, 1).'K',
            default => self::peso($amount),
        };
    }

    public static function percent(float|int|null $value, int $decimals = 1): string
    {
        return number_format((float) $value, $decimals).'%';
    }

    /** 09 Sep 2026 */
    public static function date(string|CarbonInterface|null $date): string
    {
        if (! $date) {
            return '—';
        }

        return Carbon::parse($date)->format('d M Y');
    }

    /** 09 Sep 2026, 2:41 PM */
    public static function dateTime(string|CarbonInterface|null $date): string
    {
        if (! $date) {
            return '—';
        }

        return Carbon::parse($date)->format('d M Y, g:i A');
    }

    /** Initials for the avatar fallback: "Maria Liwayway Santos" -> "MS" */
    public static function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $parts = array_values(array_filter($parts));

        if (count($parts) === 0) {
            return '?';
        }

        if (count($parts) === 1) {
            return strtoupper(substr($parts[0], 0, 2));
        }

        return strtoupper(substr($parts[0], 0, 1).substr(end($parts), 0, 1));
    }

    /**
     * Fixed status -> badge tone mapping. Any status not listed falls back to
     * "muted" rather than inventing a color.
     */
    public static function badgeTone(string $status): string
    {
        $key = strtolower(trim($status));

        $map = [
            // success
            'active' => 'success', 'paid' => 'success', 'approved' => 'success',
            'verified' => 'success', 'released' => 'success', 'current' => 'success',
            'posted' => 'success', 'completed' => 'success', 'settled' => 'success',
            'available' => 'success', 'valid' => 'success',

            // warning
            'pending' => 'warning', 'due soon' => 'warning', 'under review' => 'warning',
            'for review' => 'warning', 'on hold' => 'warning', 'partial' => 'warning',
            'restructured' => 'warning', 'dormant' => 'warning', 'credit assessment' => 'warning',
            'assigned' => 'warning', 'for check-in' => 'warning',

            // danger
            'overdue' => 'danger', 'defaulted' => 'danger', 'rejected' => 'danger',
            'past due' => 'danger', 'delinquent' => 'danger', 'failed' => 'danger',

            // info
            'disbursed' => 'info', 'submitted' => 'info', 'draft' => 'info',
            'for release' => 'info', 'new' => 'info', 'scheduled' => 'info',
            'in transit' => 'info',

            // muted
            'closed' => 'muted', 'written-off' => 'muted', 'written off' => 'muted',
            'archived' => 'muted', 'inactive' => 'muted', 'cancelled' => 'muted',
            'waived' => 'muted', 'upcoming' => 'muted', 'under maintenance' => 'muted',
        ];

        return $map[$key] ?? 'muted';
    }

    /**
     * Delta coloring by MEANING, not direction.
     *
     * $goodDirection: 'up'   -> a rise is good (portfolio, collections, savings)
     *                 'down' -> a rise is bad  (PAR, past due, defaults)
     */
    public static function deltaTone(float $delta, string $goodDirection = 'up'): string
    {
        if (abs($delta) < 0.0001) {
            return 'muted';
        }

        $rising = $delta > 0;

        return match ($goodDirection) {
            'down' => $rising ? 'bad' : 'good',
            default => $rising ? 'good' : 'bad',
        };
    }
}
