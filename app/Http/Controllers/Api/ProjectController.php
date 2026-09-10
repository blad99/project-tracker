<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\storeProjectRequest;
use App\Http\Requests\updateProjectRequest;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(): JsonResponse
    {
        $project = Project::with('dependencies')->orderByDesc('created_at')->get();

        return $this->successResponse('', $project->map(fn($p) => $p->toApiArray()));
    }

    public function show(Project $project): JsonResponse
    {
        $project->load('dependencies');
        return $this->successResponse('', $project->toApiArray());
    }

    public function store(storeProjectRequest $request): JsonResponse
    {
        $data = $request->validated();

        if(!empty($data['start_date']) && !empty($data['end_date'])) {
            $conflict = Project::findScheduleConflict($data['start_date'], $data['end_date']);
            if($conflict) {
                return $this->errorResponse("Jadwal konflik dengan project: {$conflict}", 422);
            }
        }

        $project = Project::create([
            'name'          => $data['name'],
            'start_date'    => $data['start_date'] ?? null,
            'end_date'      => $data['end_date'] ?? null
        ]);

        $depIds = $data['dependencies'] ?? [];
        foreach($depIds as $depId) {
            if ($project->wouldCreateCircular((int) $depId)) {
                $project->delete();
                return $this->errorResponse("Circular dependency terdeteksi pada project ID {$depId}", 422);
            }
        }

        if (!empty($depIds)) {
            $project->dependencies()->sync($depIds);
        }

        $project->load('dependencies');
        return $this->successResponse('Project berhasil dibuat', $project->toApiArray(), 201);
    }

    public function update(updateProjectRequest $request, Project $project): JsonResponse
    {
        $data = $request->validated();

        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            $conflict = Project::findScheduleConflict($data['start_date'], $data['end_date'], $project->id);
            if ($conflict) {
                return $this->errorResponse("Jadwal konflik dengan project {$conflict}", 422);
            }
        }
        
        $depIds = $data['dependencies'] ?? [];
        foreach ($depIds as $depId) {
            $depId = (int) $depId;
            if($depId === $project->id) continue;
            if($project->wouldCreateCircular($depId)) {
                return $this->errorResponse("Circular dependency terdeteksi pada project ID {$depId}", 422);
            }
        }

        $project->update([
            'name' => $data['name'],
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
        ]);

        $project->dependencies()->sync($depIds);
        $project->recalculate();
        $project->revalidateDependents();

        $project->load('dependencies');
        return $this->successResponse('Project berhasil diperbarui', $project->toApiArray());
    }

    public function destroy(Project $project): JsonResponse
    {
        $id = $project->id;
        $project->delete();
        return $this->successResponse('Project berhasil dihapus', ['id' => $id]);
    }
}
