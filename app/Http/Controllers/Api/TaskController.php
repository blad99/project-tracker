<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\storeTaskRequest;
use App\Http\Requests\updateTaskRequest;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Task::with(['project', 'dependencies'])
            ->orderBy('project_id')
            ->orderBy('parent_task_id')
            ->orderBy('id');

        if($request->filled('project_id')) {
            $query->where('project_id', (int) $request->project_id);
        }

        $allTasks = $query->get();

        $tree = $this->buildTree($allTasks);

        $status = $request->get('status', '');
        $search = $request->get('search', '');

        if($status !== '' || $search !== '') {
            $tree = $this->filterTree($tree, $status, $search);
        }

        return $this->successResponse('', $tree);
    }

    public function show(Task $task): JsonResponse
    {
        $task->load(['project', 'dependencies']);
        return $this->successResponse('', $task->toApiArray());
    }

    public function store(storeTaskRequest $request): JsonResponse
    {
        $data = $request->validated();
        $depIds = $data['dependencies'] ?? [];
        $parentId = $data['parent_task_id'] ?? null;

        if ($data['status'] === 'Done' && !empty($depIds)) {
            $notDone = Task::whereIn('id', $depIds)
                ->where('status', '!=', 'Done')
                ->pluck('name')
                ->toArray();
            if (!empty($notDone)) {
                return $this->errorResponse('Task tidak bisa Done. Dependency belum selesai: ' . implode(', ', $notDone), 422);
            }
        }

        $task = Task::create([
            'project_id' => $data['project_id'],
            'parent_task_id' => $parentId,
            'name' => $data['name'],
            'status' => $data['status'],
            'weight' => $data['weight']
        ]);

        foreach ($depIds as $depId) {
            $depId = (int) $depId;
            if ($depId === $task->id) continue;
            if ($task->wouldCreateCircular($depId)) {
                $task->delete();
                return $this->errorResponse("Circular dependency terdeteksi pada task ID {$depId}", 422);
            }
        }
        if (!empty($depIds)) {
            $task->dependencies()->sync($depIds);
        }

        $task->load(['project', 'dependencies']);
        $project = Project::find($data['project_id']);

        return $this->successResponse('Task berhasil dibuat', [
            'task'    => $task->toApiArray(),
            'project' => $project?->toApiArray(),
        ], 201);
    }

    public function update(updateTaskRequest $request, Task $task): JsonResponse
    {
        $data     = $request->validated();
        $depIds   = $data['dependencies'] ?? [];
        $parentId = $data['parent_task_id'] ?? null;

        if ($parentId && (int) $parentId === $task->id) {
            return $this->errorResponse('Task tidak bisa menjadi parent dirinya sendiri', 422);
        }

        if ($data['status'] === 'Done') {
            $notDone = $task->getBlockingDependencies($depIds);
            if (!empty($notDone)) {
                return $this->errorResponse('Task tidak bisa Done. Dependency belum selesai: ' . implode(', ', $notDone), 422);
            }
        }

        foreach ($depIds as $depId) {
            $depId = (int) $depId;
            if ($depId === $task->id) continue;
            if ($task->wouldCreateCircular($depId)) {
                return $this->errorResponse("Circular dependency terdeteksi pada task ID {$depId}", 422);
            }
        }

        $task->update([
            'project_id'     => $data['project_id'],
            'parent_task_id' => $parentId,
            'name'           => $data['name'],
            'status'         => $data['status'],
            'weight'         => $data['weight'],
        ]);

        $task->dependencies()->sync($depIds);
        $task->revalidateDependents();

        $task->load(['project', 'dependencies']);
        $project = Project::find($data['project_id']);

        return $this->successResponse('Task berhasil diperbarui', [
            'task'    => $task->toApiArray(),
            'project' => $project?->toApiArray(),
        ]);
    }

    public function destroy(Task $task): JsonResponse
    {
        $taskId    = $task->id;
        $projectId = $task->project_id;

        $task->delete();
        $project = Project::find($projectId);
        
        return $this->successResponse('Task berhasil dihapus', [
            'id'         => $taskId,
            'project_id' => $projectId,
            'project'    => $project?->toApiArray(),
        ]);
    }

    private function buildTree(mixed $tasks): array {
        $map = [];
        $roots = [];

        foreach($tasks as $task) {
            $arr = $task->toApiArray();
            $arr['children'] = [];
            $map[$task->id] = $arr;
        }

        foreach($map as $key => &$value) {
            $pid = $value['parent_task_id'];
            if($pid && isset($map[$pid])) {
                $map[$pid]['children'][] = &$value;
            } else {
                $roots[] = &$value;
            }
        }
        return $roots;
    }

    private function filterTree(array $nodes, string $status, string $search): array
    {
        $result = [];
        foreach($nodes as $node) {
            $filteredChildren = $this->filterTree($node['children'] ?? [], $status, $search);
            $selfMatch = $this->nodeMatches($node, $status, $search);

            if ($selfMatch) {
                $result[] = $node;
            } elseif (!empty($filteredChildren)) {
                $node['children'] = $filteredChildren;
                $result[] = $node;
            }
        }
        return $result;
    }

    private function nodeMatches(array $node, string $status, string $search): bool
    {
        $statusMatch = $status === '' || $node['status'] === $status;
        $searchMatch = $search === '' || str_contains(strtolower($node['name']), strtolower($search));
        return $statusMatch && $searchMatch;
    }
}
