<?php

namespace App\Repositories\Base;

use App\Services\UploadService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Storage;

class BaseRepository implements BaseInterface {

    /**
     * @var Model
     */
    protected Model $model;
    protected string $uploadFolder;

    /**
     * BaseRepository constructor.
     *
     * @param Model $model
     * @param string $folder
     */
    public function __construct(Model $model, string $folder = '/') {
        $this->model = $model;
        $this->uploadFolder = $folder;
    }

    public function defaultModel() {
        return $this->model;
    }

    /**
     * Get all models.
     */
    public function all(
        array $columns = ['*'],
        array $relations = [],
        array $where = []
    ): Collection {

        return $this->defaultModel()
            ->with($relations)
            ->where($where)
            ->get($columns);
    }

    /**
     * Get all trashed models.
     */
    public function allTrashed(): Collection {

        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($this->model))) {
            return $this->defaultModel()
                ->withoutGlobalScopes()
                ->onlyTrashed()
                ->get();
        }

        return new Collection();
    }

    /**
     * Find model by id.
     */
    public function findById(
        int $modelId,
        array $columns = ['*'],
        array $relations = [],
        array $appends = []
    ): ?Model {

        return $this->defaultModel()
            ->withoutGlobalScopes()
            ->select($columns)
            ->with($relations)
            ->findOrFail($modelId)
            ->append($appends);
    }

    /**
     * Find trashed model by id.
     */
    public function findTrashedById(int $modelId): ?Model {

        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($this->model))) {

            return $this->defaultModel()
                ->withoutGlobalScopes()
                ->withTrashed()
                ->findOrFail($modelId);
        }

        return $this->defaultModel()
            ->withoutGlobalScopes()
            ->findOrFail($modelId);
    }

    /**
     * Find only trashed model by id.
     */
    public function findOnlyTrashedById(int $modelId): ?Model {

        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($this->model))) {

            return $this->defaultModel()
                ->withoutGlobalScopes()
                ->onlyTrashed()
                ->findOrFail($modelId);
        }

        return $this->defaultModel()
            ->withoutGlobalScopes()
            ->findOrFail($modelId);
    }

    /**
     * Create a model.
     */
    public function create(array $payload): ?Model {

        foreach ($payload as $column => $value) {

            if ($value instanceof UploadedFile) {
                $payload[$column] = UploadService::upload(
                    $value,
                    $this->uploadFolder
                );
            }
        }

        return $this->defaultModel()->create($payload);
    }

    /**
     * Bulk insert.
     */
    public function createBulk(array $payload): bool {

        foreach ($payload as $key => $arr) {

            foreach ($arr as $column => $value) {

                if ($value instanceof UploadedFile) {
                    $payload[$key][$column] = UploadService::upload(
                        $value,
                        $this->uploadFolder
                    );
                }
            }

            $payload[$key]['created_at'] = now();
            $payload[$key]['updated_at'] = now();
        }

        return $this->defaultModel()->insert($payload);
    }

    /**
     * Update model.
     */
    public function update(int $modelId, array $payload): ?Model {

        $model = $this->findTrashedById($modelId);

        foreach ($payload as $column => $value) {

            if ($value instanceof UploadedFile) {

                if (
                    isset($model->getAttributes()[$column]) &&
                    $model->getAttributes()[$column]
                ) {
                    UploadService::delete(
                        $model->getAttributes()[$column]
                    );
                }

                $payload[$column] = UploadService::upload(
                    $value,
                    $this->uploadFolder
                );
            }
        }

        $model->update($payload);

        return $model;
    }

    /**
     * Update or create.
     */
    public function updateOrCreate(
        array $uniqueColumns,
        array $updatingColumn
    ): Model {

        foreach ($updatingColumn as $column => $value) {

            if ($value instanceof UploadedFile) {

                $updatingColumn[$column] = UploadService::upload(
                    $value,
                    $this->uploadFolder
                );
            }
        }

        return $this->defaultModel()->updateOrCreate(
            $uniqueColumns,
            $updatingColumn
        );
    }

    /**
     * Upsert.
     */
    public function upsert(
        array $payloads,
        array $uniqueColumns,
        array $updatingColumn
    ): bool {

        foreach ($payloads as $key => $payload) {

            foreach ($payload as $column => $value) {

                if ($value instanceof UploadedFile) {

                    $payloads[$key][$column] = UploadService::upload(
                        $value,
                        $this->uploadFolder
                    );
                }
            }
        }

        return $this->defaultModel()->upsert(
            $payloads,
            $uniqueColumns,
            $updatingColumn
        );
    }

    /**
     * Delete by id.
     */
    public function deleteById(int $modelId): bool {

        return $this->findById($modelId)->delete();
    }

    /**
     * Restore model.
     */
    public function restoreById(int $modelId): void {

        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($this->model))) {

            $this->findOnlyTrashedById($modelId)->restore();
        }
    }

    /**
     * Permanently delete.
     */
    public function permanentlyDeleteById(int $modelId): bool {

        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($this->model))) {

            return $this->findTrashedById($modelId)->forceDelete();
        }

        return $this->findTrashedById($modelId)->delete();
    }

    /**
     * Builder.
     */
    public function builder(): Model|Builder {

        return $this->defaultModel();
    }

    /**
     * New model instance.
     */
    public function model(): Model {

        return new $this->model();
    }

    /**
     * Upsert profile.
     */
    public function upsertProfile(
        array $payloads,
        array $uniqueColumns,
        array $updatingColumn
    ): bool {

        $existingRecords = $this->defaultModel()
            ->whereIn(
                $uniqueColumns[0],
                array_column($payloads, $uniqueColumns[0])
            )
            ->get();

        foreach ($existingRecords as $row) {

            if (
                $row->image &&
                $row->getRawOriginal('image')
            ) {

                if (
                    Storage::disk('public')
                        ->exists($row->getRawOriginal('image'))
                ) {

                    Storage::disk('public')
                        ->delete($row->getRawOriginal('image'));
                }
            }
        }

        foreach ($payloads as $key => $payload) {

            foreach ($payload as $column => $value) {

                if ($value instanceof UploadedFile) {

                    $payloads[$key][$column] = UploadService::upload(
                        $value,
                        $this->uploadFolder
                    );
                }
            }
        }

        return $this->defaultModel()->upsert(
            $payloads,
            $uniqueColumns,
            $updatingColumn
        );
    }
}