<?php

namespace App\Vito\Plugins\Thefinq\VitoUptimePlugin\Services;

/**
 * Class strings of Vito's UI primitives (shadcn), so the Blade views look like the rest of
 * the panel. They are copied from what Vito renders; only classes that exist in Vito's
 * compiled stylesheet are used.
 */
class Ui
{
    private const string BUTTON_BASE = "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-[color,box-shadow] disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]";

    public static function button(): string
    {
        return self::BUTTON_BASE.' border border-input bg-background shadow-xs hover:bg-muted dark:bg-input/10 dark:hover:bg-input/20 h-9 px-4 py-2 has-[>svg]:px-3';
    }

    public static function buttonSmall(): string
    {
        return self::BUTTON_BASE.' border border-input bg-background shadow-xs hover:bg-muted dark:bg-input/10 dark:hover:bg-input/20 h-8 rounded-md px-3 has-[>svg]:px-2.5';
    }

    public static function buttonPrimary(): string
    {
        return self::BUTTON_BASE.' relative bg-primary text-primary-foreground shadow-xs-skeuomorphic hover:bg-primary/90 h-9 px-4 py-2 has-[>svg]:px-3';
    }

    public static function buttonDanger(): string
    {
        return self::BUTTON_BASE.' border border-input bg-background shadow-xs hover:bg-muted dark:bg-input/10 dark:hover:bg-input/20 h-9 px-4 py-2 text-red-600';
    }

    public static function input(): string
    {
        return 'file:text-foreground placeholder:text-muted-foreground dark:bg-input/30 border-input flex h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]';
    }

    public static function select(): string
    {
        return self::input().' appearance-none';
    }

    public static function label(): string
    {
        return 'text-sm leading-none font-medium select-none';
    }

    public static function hint(): string
    {
        return 'text-muted-foreground text-xs';
    }

    public static function error(): string
    {
        return 'text-red-600 text-xs';
    }

    public static function card(): string
    {
        return 'bg-card text-card-foreground flex flex-col rounded-xl border shadow-xs';
    }

    public static function cardHeader(): string
    {
        return 'flex flex-col gap-1.5 border-b p-4';
    }

    public static function cardTitle(): string
    {
        return 'leading-none font-semibold';
    }

    public static function cardDescription(): string
    {
        return 'text-muted-foreground text-sm';
    }

    public static function cardContent(): string
    {
        return 'p-4';
    }

    public static function cardFooter(): string
    {
        return 'flex items-center border-t p-4 gap-2';
    }

    public static function tableWrapper(): string
    {
        return 'relative overflow-hidden rounded-md border shadow-xs';
    }

    public static function table(): string
    {
        return 'w-full caption-bottom text-sm';
    }

    public static function thead(): string
    {
        return 'bg-card [&_tr]:border-b';
    }

    public static function tr(): string
    {
        return 'hover:bg-muted/50 border-b transition-colors';
    }

    public static function th(): string
    {
        return 'text-foreground h-10 px-4 text-left align-middle font-medium whitespace-nowrap';
    }

    public static function td(): string
    {
        return 'p-4 align-middle';
    }

    /**
     * Status pill. Known states: up, down, pending, maintenance, paused, unknown.
     */
    public static function badge(string $state): string
    {
        $base = 'inline-flex items-center justify-center rounded-md border border-transparent px-2 py-0.5 text-xs font-medium whitespace-nowrap ';

        $color = match ($state) {
            'up' => 'bg-green-100 text-green-800',
            'down' => 'bg-red-100 text-red-800',
            'pending', 'maintenance' => 'bg-yellow-100 text-yellow-800',
            default => 'bg-gray-100 text-gray-800',
        };

        return $base.$color;
    }

    public static function dot(string $state): string
    {
        $color = match ($state) {
            'up' => 'bg-green-500',
            'down' => 'bg-red-500',
            'pending', 'maintenance' => 'bg-yellow-500',
            default => 'bg-gray-500',
        };

        return 'inline-block size-2 rounded-full '.$color;
    }
}
