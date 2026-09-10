<?php

namespace App\Observers;

use App\Models\Project;

class ProjectObserver
{
   public function saved(Project $project): void
   {
        $project->revalidateDependents();
   }
}
