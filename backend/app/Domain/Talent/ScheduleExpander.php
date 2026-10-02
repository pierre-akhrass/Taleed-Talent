<?php

namespace App\Domain\Talent;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

class ScheduleExpander
{
    /** @param array{cadence: string, startDate: string, endDate: string, weekdays?: array<int, int>} $schedule */
    public function expand(array $schedule, string $month, string $theme): array
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            throw new InvalidArgumentException('Month must use YYYY-MM format.');
        }

        $start = $this->date($schedule['startDate']);
        $end = $this->date($schedule['endDate']);
        $monthStart = $this->date($month.'-01');
        $monthEnd = $monthStart->modify('last day of this month');

        if ($start > $end || $start < $monthStart || $end > $monthEnd) {
            throw new InvalidArgumentException('Schedule dates must be ordered and inside the selected month.');
        }

        if ($schedule['cadence'] === 'one_off') {
            if ($theme !== 'develop' || $start != $end) {
                throw new InvalidArgumentException('One-off schedules require one date in Develop.');
            }

            return [$start->format('Y-m-d')];
        }

        if (! in_array($schedule['cadence'], ['daily', 'weekly'], true)) {
            throw new InvalidArgumentException('Choose a supported schedule cadence.');
        }

        $weekdays = $schedule['weekdays'] ?? [];
        if ($schedule['cadence'] === 'weekly' && ($weekdays === [] || array_diff($weekdays, range(0, 6)) !== [])) {
            throw new InvalidArgumentException('Weekly schedules require valid weekdays.');
        }

        $dates = [];
        for ($current = $start; $current <= $end; $current = $current->add(new DateInterval('P1D'))) {
            if ($schedule['cadence'] === 'daily' || in_array((int) $current->format('w'), $weekdays, true)) {
                $dates[] = $current->format('Y-m-d');
            }
        }

        if ($dates === []) {
            throw new InvalidArgumentException('No dates match the selected weekdays.');
        }

        return $dates;
    }

    private function date(string $value): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('UTC'));
        if (! $date || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException('Schedule dates must be valid YYYY-MM-DD dates.');
        }

        return $date;
    }
}
