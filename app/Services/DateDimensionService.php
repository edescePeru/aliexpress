<?php

namespace App\Services;

use Carbon\Carbon;
use App\DateDimension;
use Carbon\CarbonPeriod;

class DateDimensionService
{
    public function populate($force = false)
    {
        if (!$force && DateDimension::exists()) {
            return;
        }

        if ($force) {
            DateDimension::truncate();
        }

        $dataToInsert = [];
        $dates = CarbonPeriod::create('2024-01-01', '2040-12-31');

        foreach ($dates as $date) {

            // Opcional: asegurar timezone/locale consistentes
            $date = $date->copy()->timezone('America/Lima')->locale('es');

            $quarterDetails = $this->getQuarterDetails($date);

            $dataToInsert[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->day,
                'month' => $date->month,
                'year' => $date->year,
                'day_name' => $date->dayName,
                'day_suffix' => $this->getDaySuffix($date->day),

                // OJO: dayOfWeek de Carbon es 0..6 (domingo=0)
                'day_of_week' => $date->dayOfWeek,

                'day_of_year' => $date->dayOfYear,
                'is_weekend' => (int) $date->isWeekend(),
                'week' => $date->week,
                'week_of_month' => $date->weekOfMonth,
                'week_of_year' => $date->weekOfYear,
                'month_name' => ucfirst($date->monthName),
                'month_year' => $date->format('mY'),
                'month_name_year' => ucfirst(substr($date->monthName, 0, 3)).'-'.$date->year,
                'quarter' => $quarterDetails['value'],
                'quarter_name' => $quarterDetails['name'],

                // ✅ NUEVOS CAMPOS ISO
                'iso_year' => $date->isoWeekYear,
                'iso_week' => $date->isoWeek,
                'iso_day_of_week' => $date->dayOfWeekIso, // 1..7 (lunes..domingo)

                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (collect($dataToInsert)->chunk(500) as $chunk) {
            DateDimension::insert($chunk->toArray());
        }
    }

    public function getMonthsOfYear($year)
    {
        $months = DateDimension::where('year', $year)->distinct()->get(['month', 'month_name']);

        return $months;
    }

    public function getWeeksOfMonthsOfYear($month, $year)
    {
        $weeks = DateDimension::where('year', $year)
            ->where('month', $month)
            ->distinct()->get(['week']);

        return $weeks;
    }

    public function getYearsOfSystem()
    {
        $years = DateDimension::distinct()->get(['year']);
        return $years;
    }

    /**
     * Get Quarter details
     * @OTE - Depending on your companies quarter update the map and logic below
     *
     * @param Carbon $date
     * @return array
     */
    private function getQuarterDetails(Carbon $date)
    {
        $quarterMonthMap = [
            1 => ['value' => 1, 'name' => 'Trim-1'],
            2 => ['value' => 1, 'name' => 'Trim-1'],
            3 => ['value' => 1, 'name' => 'Trim-1'],
            4 => ['value' => 2, 'name' => 'Trim-2'],
            5 => ['value' => 2, 'name' => 'Trim-2'],
            6 => ['value' => 2, 'name' => 'Trim-2'],
            7 => ['value' => 3, 'name' => 'Trim-3'],
            8 => ['value' => 3, 'name' => 'Trim-3'],
            9 => ['value' => 3, 'name' => 'Trim-3'],
            10 => ['value' => 4, 'name' => 'Trim-4'],
            11 => ['value' => 4, 'name' => 'Trim-4'],
            12 => ['value' => 4, 'name' => 'Trim-4'],
        ];

        $output['value'] = $quarterMonthMap[$date->month]['value'];
        $output['name'] = $quarterMonthMap[$date->month]['name'];

        return $output;
    }

    /**
     * Get the Day Suffix
     * Copied logic from - https://www.mssqltips.com/sqlservertip/4054/creating-a-date-dimension-or-calendar-table-in-sql-server/
     *
     * @param $day
     * @return string
     */
    private function getDaySuffix($day)
    {
        if ($day/10 == 1) {
            return "th";
        }
        $right = substr($day, -1);

        if ($right == 1) {
            return 'st';
        }

        if ($right == 2) {
            return 'nd';
        }

        if ($right == 3) {
            return 'rd';
        }

        return 'th';
    }
}