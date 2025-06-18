<?php

namespace MamunHoque\CrudBuilder\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait HelperTrait
{
    /**
     * Apply sorting to query
     */
    private function applySorting($query, Request $request): void
    {
        $sortBy = $request->input('sortBy', 'id');
        $sortDesc = $request->boolean('sortDesc', true) ? 'desc' : 'asc';
        $query->orderBy($sortBy, $sortDesc);
    }

    /**
     * Apply search to query
     */
    private function applySearch($query, ?string $searchValue, array $searchKeys): void
    {
        if ($searchValue) {
            $query->where(function ($query) use ($searchValue, $searchKeys) {
                foreach ($searchKeys as $key) {
                    $query->orWhereRaw('LOWER(' . $key . ') LIKE ?', ['%' . strtolower($searchValue) . '%']);
                }
            });
        }
    }

    /**
     * Apply pagination or get all results
     */
    private function paginateOrGet($query, Request $request, $optional = null): Collection|LengthAwarePaginator|array
    {
        if ($request->boolean('pagination', true)) {
            $itemsPerPage = $request->input('per_page', 10);
            $currentPage = Paginator::resolveCurrentPage('page');

            $items = $query->paginate($itemsPerPage, ['*'], 'page', $currentPage);

            return [
                'optional' => $optional ? $optional : null,
                'data' => $items->items(),
                'current_page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total_items' => $items->total(),
                'total_pages' => $items->lastPage(),
            ];
        } elseif (!empty($request->input('per_page'))) {
            $query->take($request->input('per_page'));
        }

        return $query->get();
    }

    /**
     * Apply active filter
     */
    public function applyActive(Builder $query, Request $request): Builder
    {
        return $query->when($request->has('is_active'), function ($q) use ($request) {
            $isActive = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if (!is_null($isActive)) {
                $q->where('is_active', $isActive);
            }
        });
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, $request, $filters)
    {
        foreach ($filters as $key => $operator) {
            if (!$request->filled($key)) {
                continue;
            }

            $value = $request->input($key);

            if (in_array($operator, ['=', 'like', '>', '<', '>=', '<='])) {
                $query->where($key, $operator, $operator === 'like' ? "%$value%" : $value);
            }
        }
    }

    /**
     * Success response
     */
    protected function successResponse($data, $message, $statusCode = 200): JsonResponse
    {
        $array = [
            'status' => true,
            'data' => $data,
            'message' => $message,
        ];

        return response()->json($array, $statusCode);
    }

    /**
     * Error response
     */
    protected function errorResponse($errors, $message, $statusCode, $error = null): JsonResponse
    {
        $array = [
            'status' => false,
            'error' => $error,
            'errors' => $errors,
            'message' => $message,
        ];

        return response()->json($array, $statusCode);
    }

    /**
     * Upload file to S3
     */
    protected function s3FileUpload($request, $fileName, $path)
    {
        if (!$request->hasFile($fileName)) {
            return '';
        }

        try {
            $file = $request->file($fileName);
            $path = rtrim($path, '/') . '/';
            $extension = $file->getClientOriginalExtension();
            $uniqueName = Str::uuid() . '.' . $extension;
            $key = $path . $uniqueName;

            // Use Laravel's Storage facade for S3 upload
            $uploaded = Storage::disk('s3')->put($key, file_get_contents($file->getPathname()));

            if ($uploaded) {
                return [
                    'path' => $key,
                    'full_url' => Storage::disk('s3')->url($key),
                ];
            }

            return '';
        } catch (\Exception $e) {
            \Log::error('S3 Upload error: ' . $e->getMessage());
            throw new \Exception('S3 Upload error: ' . $e->getMessage());
        }
    }

    /**
     * Upload file to local storage
     */
    protected function localFileUpload($request, $fileName, $path): bool|string
    {
        if (!$request->hasFile($fileName)) {
            return '';
        }

        try {
            $file = $request->file($fileName);
            $path = rtrim($path, '/');
            $filePath = Storage::disk('public')->put($path, $file);

            if ($filePath) {
                return $filePath;
            }

            return '';
        } catch (\Exception $e) {
            \Log::error('File upload error: ' . $e->getMessage());
            throw new \Exception('File upload error: ' . $e->getMessage());
        }
    }

    /**
     * Upload multiple files
     */
    protected function uploadMultipleFiles(Request $request, $fileName, $path)
    {
        if (!$request->hasFile($fileName)) {
            return [];
        }

        try {
            $files = $request->file($fileName);
            $path = rtrim($path, '/');
            $filePaths = [];
            
            foreach ($files as $file) {
                $filePaths[] = Storage::disk('public')->put($path, $file);
            }

            return $filePaths;
        } catch (\Exception $e) {
            \Log::error('File upload error: ' . $e->getMessage());
            throw new \Exception('File upload error: ' . $e->getMessage());
        }
    }

    /**
     * Convert string to class name
     */
    protected function convertToClassName($input)
    {
        $parts = explode('_', $input);
        $classNameParts = array_map('ucfirst', $parts);

        return 'App\Models\\' . implode('', $classNameParts);
    }

    /**
     * Convert model to snake case
     */
    protected function modelToSnakeCase($input)
    {
        $input = str_replace('App\\Models\\', '', $input);

        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }

    /**
     * Convert camelCase to snake_case
     */
    protected function camelToSnakeCase($input)
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }

    /**
     * Apply e-commerce filters
     */
    public function applyEComFilters(Builder $query, Request $request): Builder
    {
        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->has('min_price') && $request->has('max_price')) {
            $query->whereBetween('price', [$request->input('min_price'), $request->input('max_price')]);
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }

        return $query;
    }

    /**
     * Apply e-commerce sorting
     */
    public function applyEComSorting(Builder $query, Request $request): Builder
    {
        $sortType = $request->input('sort');

        switch ($sortType) {
            case 'price_low':
                $query->orderBy('price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        return $query;
    }
}
