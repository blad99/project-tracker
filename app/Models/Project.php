<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
