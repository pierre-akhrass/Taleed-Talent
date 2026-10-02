<?php

namespace App\Domain\Talent;

class PickThreeValidator
{
    /** @param array<int, array{theme: string, scope: string, available: bool}> $activities */
    public function passes(string $theme, array $activities): bool
    {
        if (count($activities) !== 3) {
            return false;
        }

        $scopes = [];
        foreach ($activities as $activity) {
            if ($activity['theme'] !== $theme || ! $activity['available'] || ! in_array($activity['scope'], ['individual', 'culture', 'team'], true)) {
                return false;
            }
            $scopes[] = $activity['scope'];
        }

        return count(array_unique($scopes)) === 3;
    }
}
