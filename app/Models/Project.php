<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

use function PHPUnit\Framework\matches;

#[Fillable([
    'name',
    'status',
    'completion_progress',
    'start_date',
    'end_date'
])]

class Project extends Model
{
    protected $casts = [
        'start_date'            => 'date',
        'end_date'              => 'date',
        'completion_progress'   => 'float',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            Project::class,
            'project_dependencies',
            'project_id',
            'depends_on_project_id'
        );
    }

    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(
            Project::class,
            'project_dependencies',
            'depends_on_project_id',
            'project_id'
        );
    }

    public function toApiArray(): array
    {
        return [
            'id'                    => $this->id,
            'name'                  => $this->name,
            'status'                => $this->status,
            'completion_progress'   => (float) $this->completion_progress,
            'start_date'            => $this->start_date?->format('Y-m-d'),
            'end_date'              => $this->end_date?->format('Y-m-d'),
            'created_at'            => $this->created_at?->toIso8601String(),
            'dependencies'          => $this->dependencies()->map(fn($d) => [
                'id'     => $d->id,
                'name'   => $d->name,
                'status' => $d->status
            ])->values()->toArray(),
        ];
    }

    public function recalculate(): void
    {
        $tasks = $this->tasks()->get(['status', 'weight']);

        if ($tasks->isEmpty()) {
            $this->update(['status' => 'Draft', 'completion_progress' => 0.00]);
            return;
        }

        $totalWeight = $tasks->sum('weight');
        $doneWeight = $tasks->where('status', 'Done')->sum('weight');

        $progress = $totalWeight > 0 ? round($doneWeight / $totalWeight * 100, 2) : 0;
        $status = $this->statusFromTask($tasks);

        $this->update(['status' => $status, 'completion_progress' => $progress]);
    }

    public function revalidateDependents(): void
    {
        foreach ($this->dependents as $dependent) {
            $dependent->recalculate();
            $dependent->revalidateDependents();
        }
    }

    public function wouldCreateCircular(int $newDepId): bool
    {
       $visited = [];
       $stack = [$newDepId];

       while (!empty($stack)) {
            $currentId = array_pop($stack);
            if($currentId === $this->id) return true;

            if(isset($visited[$currentId])) continue;

            $visited[$currentId] = true;

            $deps = Project::find($currentId)?->dependencies()->pluck('id')->toArray() ?? [];
            foreach($deps as $depId) {
                $stack[] = $depId;
            }
       }

       return false;
    }

    public function findScheduleConflict(string $start, string $end, int $excludeId = 0): ?string
    {
        $conflict = static::where('id', '!=', $excludeId)
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start)
            ->first(['name']);

            return $conflict?->name;
    }


    private function statusFromTask(mixed $tasks): string
    {
        $hasDraft = $tasks->contains('status', 'Draft');
        $hasInProgress = $tasks->contains('status', 'In Progress');
        $hasDone = $tasks->contains('status', 'Done');

        $status = match(true) {
            $hasDone && !$hasInProgress && !$hasDraft => 'Done',
            $hasDone || $hasInProgress => 'In Progress',
            default => 'Draft',
        };

        if ($status !== 'Draft') {
            $blockedDep = $this->dependencies()->where('status', '!=', 'Done')->exists();
            if ($blockedDep) {
                $status = 'Blocked';
            }
        }

        return $status;
    }
}
