<?php

namespace App;

enum AppSettingPeriod:string
{
    case OneDay = '1_day';
    case OneWeek = '1_week';
    case TwoWeeks = '2_weeks';
    case OneMonth = '1_month';
    case TwoMonths = '2_months';
    case SixMonths = '6_months';
    case OneYear = '1_year';

    public function toCarbonInterval()
    {
        return match ($this) {
            self::OneDay => now()->subDay(),
            self::OneWeek => now()->subWeek(),
            self::TwoWeeks => now()->subWeeks(2),
            self::OneMonth => now()->subMonth(),
            self::TwoMonths => now()->subMonths(2),
            self::SixMonths => now()->subMonths(6),
            self::OneYear => now()->subYear(),
        };
    }
}
