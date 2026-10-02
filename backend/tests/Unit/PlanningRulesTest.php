<?php

namespace Tests\Unit;

use App\Domain\Talent\PickThreeValidator;
use App\Domain\Talent\PlanMetricsCalculator;
use App\Domain\Talent\ScheduleExpander;
use InvalidArgumentException;
use JsonException;
use Tests\TestCase;

class PlanningRulesTest extends TestCase
{
    /** @throws JsonException */
    public function test_shared_pick_three_fixtures_match_the_server_validator(): void
    {
        $fixtures = json_decode(file_get_contents(dirname(base_path()).'/contracts/fixtures/planning-rules.json'), true, 512, JSON_THROW_ON_ERROR);
        $validator = app(PickThreeValidator::class);

        foreach ($fixtures['pickThree'] as $fixture) {
            $activities = array_map(fn (array $activity): array => [
                'theme' => $activity['theme'],
                'scope' => $activity['scope'],
                'available' => $activity['available'],
            ], $fixture['activities']);

            $this->assertSame($fixture['valid'], $validator->passes($fixture['theme'], $activities), $fixture['name']);
        }
    }

    /** @throws JsonException */
    public function test_shared_schedule_fixtures_match_the_server_expander(): void
    {
        $fixtures = json_decode(file_get_contents(dirname(base_path()).'/contracts/fixtures/planning-rules.json'), true, 512, JSON_THROW_ON_ERROR);
        $expander = app(ScheduleExpander::class);

        foreach ($fixtures['schedules'] as $fixture) {
            $schedule = [
                'cadence' => $fixture['cadence'],
                'startDate' => $fixture['startDate'],
                'endDate' => $fixture['endDate'],
                'weekdays' => $fixture['weekdays'],
            ];

            if ($fixture['dates'] === null) {
                try {
                    $expander->expand($schedule, '2026-10', $fixture['theme']);
                    $this->fail($fixture['name']);
                } catch (InvalidArgumentException) {
                    $this->assertTrue(true);
                }

                continue;
            }

            $this->assertSame($fixture['dates'], $expander->expand($schedule, '2026-10', $fixture['theme']), $fixture['name']);
        }
    }

    /** @throws JsonException */
    public function test_shared_metric_fixture_matches_server_calculation(): void
    {
        $fixtures = json_decode(file_get_contents(dirname(base_path()).'/contracts/fixtures/planning-rules.json'), true, 512, JSON_THROW_ON_ERROR);
        $result = app(PlanMetricsCalculator::class)->calculate(
            $fixtures['metrics']['occurrences'],
            $fixtures['metrics']['commitments'],
        );

        $this->assertSame($fixtures['metrics']['expected'], $result);
    }
}
