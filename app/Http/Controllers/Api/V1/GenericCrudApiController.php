<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\MobileCrudRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * One API for the Settings pages listed in MobileCrudRegistry.
 *
 *   GET    crud/{resource}        schema (fields, options, permissions) + rows
 *   POST   crud/{resource}        create          (needs the web's "Add ..." permission)
 *   PUT    crud/{resource}/{id}   update          (needs "Edit ...")
 *   DELETE crud/{resource}/{id}   delete          (needs "Delete ...")
 */
class GenericCrudApiController extends Controller
{
    public function index(Request $request, string $resource): JsonResponse
    {
        $config = MobileCrudRegistry::get($resource);
        if (!$config) {
            return $this->notFound();
        }
        if (!$this->allowed($request, $config['tile'] ?? null)) {
            return $this->forbidden();
        }

        $options = $this->options($config);
        $model = $config['model'];

        // The web pages filter by Start Date / End Date on the page's date column.
        $dateColumn = $config['date_filter'] ?? collect($config['fields'])->firstWhere('type', 'date')['name'] ?? null;
        $rows = $model::query()
            ->when($dateColumn && $request->filled('start_date'), fn ($q) => $q->whereDate($dateColumn, '>=', $request->input('start_date')))
            ->when($dateColumn && $request->filled('end_date'), fn ($q) => $q->whereDate($dateColumn, '<=', $request->input('end_date')))
            ->orderBy($config['order'] ?? 'id')
            ->get();

        return response()->json([
            'success' => true,
            'schema' => [
                'resource' => $resource,
                'title' => $config['title'],
                'list' => $config['list'],
                'date_filter' => $dateColumn,
                'fields' => array_map(function ($field) use ($options) {
                    unset($field['source']);
                    if (isset($options[$field['name']])) {
                        $field['options'] = $options[$field['name']];
                    }

                    return $field;
                }, $config['fields']),
                'permissions' => [
                    'add' => $this->allowed($request, $config['perm']['add'] ?? null),
                    'edit' => $this->allowed($request, $config['perm']['edit'] ?? null),
                    'delete' => $this->allowed($request, $config['perm']['delete'] ?? null),
                ],
            ],
            'data' => $rows->map(fn ($row) => $this->format($row, $config, $options))->values(),
            'meta' => ['total' => $rows->count()],
        ]);
    }

    public function store(Request $request, string $resource): JsonResponse
    {
        $config = MobileCrudRegistry::get($resource);
        if (!$config) {
            return $this->notFound();
        }
        if (!$this->allowed($request, $config['perm']['add'] ?? null)) {
            return $this->forbidden();
        }

        try {
            $model = $config['model'];
            $row = $model::create($this->validated($request, $config));

            return response()->json([
                'success' => true,
                'data' => $this->format($row, $config, $this->options($config)),
                'message' => $config['title'] . ' saved.',
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error("Crud store {$resource}: " . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to save.'], 500);
        }
    }

    public function update(Request $request, string $resource, int $id): JsonResponse
    {
        $config = MobileCrudRegistry::get($resource);
        if (!$config) {
            return $this->notFound();
        }
        if (!$this->allowed($request, $config['perm']['edit'] ?? null)) {
            return $this->forbidden();
        }

        try {
            $model = $config['model'];
            $row = $model::findOrFail($id);
            $row->update($this->validated($request, $config));

            return response()->json([
                'success' => true,
                'data' => $this->format($row->fresh(), $config, $this->options($config)),
                'message' => $config['title'] . ' updated.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound();
        } catch (\Throwable $e) {
            Log::error("Crud update {$resource}: " . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to update.'], 500);
        }
    }

    public function destroy(Request $request, string $resource, int $id): JsonResponse
    {
        $config = MobileCrudRegistry::get($resource);
        if (!$config) {
            return $this->notFound();
        }
        if (!$this->allowed($request, $config['perm']['delete'] ?? null)) {
            return $this->forbidden();
        }

        $model = $config['model'];
        $row = $model::find($id);
        if (!$row) {
            return $this->notFound();
        }

        try {
            $row->delete();
        } catch (\Throwable $e) {
            Log::error("Crud delete {$resource}: " . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to delete. It may be in use.'], 409);
        }

        return response()->json(['success' => true, 'message' => $config['title'] . ' deleted.']);
    }

    // ── helpers ────────────────────────────────────────────────────────────

    private function validated(Request $request, array $config): array
    {
        $rules = [];
        foreach ($config['fields'] as $field) {
            $rule = [$field['required'] ?? false ? 'required' : 'nullable'];
            $rule[] = match ($field['type']) {
                'number' => 'numeric',
                'boolean' => 'boolean',
                'date' => 'date',
                // same limits as the web's generic upload
                'file' => 'file|mimes:png,jpg,jpeg,csv,txt,xlx,xls,xlsx,doc,docx,pdf|max:4048',
                'textarea' => 'string',
                default => 'string|max:255',
            };
            $rules[$field['name']] = implode('|', $rule);
        }

        $data = $request->validate($rules);

        // Only the registered fields are ever written.
        $data = array_intersect_key($data, array_flip(array_column($config['fields'], 'name')));

        // Files are stored like the web does (public disk, uploads/, path kept as /storage/...).
        foreach ($config['fields'] as $field) {
            if (($field['type'] ?? '') !== 'file') {
                continue;
            }
            unset($data[$field['name']]);
            if ($upload = $request->file($field['name'])) {
                $name = time() . '_' . $upload->getClientOriginalName();
                $data[$field['name']] = '/storage/' . $upload->storeAs('uploads', $name, 'public');
            }
        }

        return $data;
    }

    /** @return array<string, array<int, array{value:string,label:string}>> */
    private function options(array $config): array
    {
        $out = [];
        foreach ($config['fields'] as $field) {
            if (($field['type'] ?? '') !== 'select') {
                continue;
            }
            if (isset($field['options'])) {
                $out[$field['name']] = $field['options'];
            } elseif (isset($field['source'])) {
                $src = $field['source'];
                $model = $src['model'];
                $out[$field['name']] = $model::orderBy($src['label'])->get()->map(fn ($m) => [
                    'value' => (string) $m->{$src['value']},
                    'label' => isset($src['prefix'])
                        ? $m->{$src['prefix']} . ' - ' . $m->{$src['label']}
                        : (string) $m->{$src['label']},
                ])->values()->all();
            }
        }

        return $out;
    }

    private function format($row, array $config, array $options): array
    {
        $out = ['id' => $row->id];
        $labels = [];
        foreach ($config['fields'] as $field) {
            $name = $field['name'];
            $value = $row->{$name};
            $out[$name] = $field['type'] === 'boolean' ? (bool) $value : ($field['type'] === 'file' && $value ? url($value) : $value);

            if ($field['type'] === 'select') {
                foreach ($options[$name] ?? [] as $opt) {
                    if ((string) $opt['value'] === (string) $value) {
                        $labels[$name] = $opt['label'];
                        break;
                    }
                }
            }
        }
        $out['labels'] = $labels;
        $out['created_at'] = optional($row->created_at)->format('Y-m-d H:i:s');

        return $out;
    }

    /** A missing permission name means the web checks nothing there. */
    private function allowed(Request $request, ?string $permission): bool
    {
        return $permission === null || $request->user()->can($permission);
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Not found.'], 404);
    }

    private function forbidden(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'You do not have permission to perform this action.'], 403);
    }
}
