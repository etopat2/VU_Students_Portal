<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SensitiveActionObserver
{
    public function created(Model $model): void
    {
        if ($model instanceof Student) {
            return;
        }

        $this->writeLog($model, 'created', [], $this->auditedValues($model->getAttributes()));
    }

    public function updated(Model $model): void
    {
        $changed = $this->auditedValues($model->getChanges());

        if ($model instanceof Student) {
            $changed = Arr::only($changed, ['clearance_status']);
        }

        if ($changed === []) {
            return;
        }

        $oldValues = [];

        foreach (array_keys($changed) as $key) {
            $oldValues[$key] = $model->getRawOriginal($key);
        }

        $oldValues = $this->auditedValues($oldValues);
        $this->writeLog($model, 'updated', $oldValues, $changed);
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function auditedValues(array $values): array
    {
        return Arr::except($values, ['created_at', 'updated_at', 'deleted_at']);
    }

    /**
     * @param array<string, mixed> $oldValues
     * @param array<string, mixed> $newValues
     */
    private function writeLog(Model $model, string $event, array $oldValues, array $newValues): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => Str::snake(class_basename($model)).'.'.$event,
            'subject_type' => $model::class,
            'subject_id' => $model->getKey(),
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip_address' => request()->ip(),
        ]);
    }
}
