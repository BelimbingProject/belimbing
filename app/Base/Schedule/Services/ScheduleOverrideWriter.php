<?php

namespace App\Base\Schedule\Services;

use App\Base\Schedule\Models\ScheduleOverride;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Persists scheduler-owned cadence overrides without overwriting a concurrent
 * editor's change.
 */
class ScheduleOverrideWriter
{
    public function saveIfUnchanged(string $key, string $name, string $expression, string $expectedExpression): bool
    {
        return app(ScheduleConfigurationGate::class)->synchronize($key, function () use ($key, $name, $expression, $expectedExpression): bool {
            if ($expectedExpression === '') {
                return $this->createIfAbsent($key, $name, $expression);
            }

            return $this->updateIfExpected($key, $name, $expression, $expectedExpression);
        });
    }

    private function createIfAbsent(string $key, string $name, string $expression): bool
    {
        try {
            // The unique-index violation is the staleness verdict. On
            // PostgreSQL a failed statement aborts its enclosing transaction,
            // so use a savepoint to keep the configuration gate usable.
            DB::transaction(fn () => ScheduleOverride::query()->create([
                'source' => 'scheduler',
                'key' => $key,
                'name' => $name,
                'expression' => $expression,
            ]));
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        return true;
    }

    private function updateIfExpected(string $key, string $name, string $expression, string $expectedExpression): bool
    {
        $override = ScheduleOverride::query()
            ->where('source', 'scheduler')
            ->where('key', $key)
            ->lockForUpdate()
            ->first();

        if ($override === null || $override->expression !== $expectedExpression) {
            return false;
        }

        $override->fill(['name' => $name, 'expression' => $expression])->save();

        return true;
    }
}
