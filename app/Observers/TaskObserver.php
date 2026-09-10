<?php

namespace App\Observers;

use App\Models\Task;

class TaskObserver
{
    public function saved(Task $task): void
    {
        $project = $task->project;
        if ($project) {
            $project->recalculate();
            $project->revalidateDependents();
        }
    }

    public function delete(Task $task): void
    {
        $project = $task->project;
        if ($project) {
            $project->recalculate();
            $project->revalidateDependents();
        }
    }
}
