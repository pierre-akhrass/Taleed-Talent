<?php

namespace App\Domain\Talent;

class PlanMetricsCalculator
{
    /**
     * @param  array<int, array{commitmentId: string, status: string}>  $occurrences
     * @param  array<int, array{id: string, scope: string}>  $commitments
     * @return array{scheduled: int, completed: int, blocked: int, inProgress: int, cancelled: int, eligible: int, rate: int|null, coverage: array<int, string>}
     */
    public function calculate(array $occurrences, array $commitments): array
    {
        $scheduled = array_values(array_filter($occurrences, fn (array $occurrence): bool => $occurrence['status'] !== 'superseded'));
        $eligible = array_values(array_filter($scheduled, fn (array $occurrence): bool => $occurrence['status'] !== 'cancelled'));
        $completed = array_values(array_filter($eligible, fn (array $occurrence): bool => $occurrence['status'] === 'completed'));
        $completedCommitmentIds = array_unique(array_column($completed, 'commitmentId'));
        $deliveredScopes = [];

        foreach ($commitments as $commitment) {
            if (in_array($commitment['id'], $completedCommitmentIds, true)) {
                $deliveredScopes[] = $commitment['scope'];
            }
        }

        $coverage = array_values(array_filter(
            ['individual', 'culture', 'team'],
            fn (string $scope): bool => in_array($scope, $deliveredScopes, true),
        ));

        return [
            'scheduled' => count($scheduled),
            'completed' => count($completed),
            'blocked' => count(array_filter($eligible, fn (array $occurrence): bool => $occurrence['status'] === 'blocked')),
            'inProgress' => count(array_filter($eligible, fn (array $occurrence): bool => $occurrence['status'] === 'in_progress')),
            'cancelled' => count(array_filter($scheduled, fn (array $occurrence): bool => $occurrence['status'] === 'cancelled')),
            'eligible' => count($eligible),
            'rate' => $eligible === [] ? null : (int) round(count($completed) / count($eligible) * 100),
            'coverage' => $coverage,
        ];
    }
}
