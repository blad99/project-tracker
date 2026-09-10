<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'project_id',
    'parent_task_id',
    'name',
    'status',
    'weight'
])]

class Task extends Model
{
    protected $casts = [
        'weight'            => 'integer',
        'parent_tast_id'    => 'integer',
        'project_id'        => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }

    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            Task::class,
            'task_dependencies',
            'task_id',
            'depends_on_task_id'
        );
    }

    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(
            Task::class,
            'task_dependencies',
            'depends_on_task_id',
            'task_id'
        );
    }

    public function toApiArray(): array
    {
        return [
            'id'             => $this->id,
            'project_id'     => $this->project_id,
            'project_name'   => $this->project?->name,
            'parent_task_id' => $this->parent_task_id,
            'name'           => $this->name,
            'status'         => $this->status,
            'weight'         => $this->weight,
            'created_at'     => $this->created_at?->toIso8601String(),
            'dependencies'   => $this->dependencies()->map(fn($d) => [
                'id'     => $d->id,
                'name'   => $d->name,
                'status' => $d->status
            ])->values()->toArray(),
            'children'       => [],
        ];
    }
}
