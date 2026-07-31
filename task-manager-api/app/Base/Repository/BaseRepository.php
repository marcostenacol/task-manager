<?php

namespace App\Base\Repository;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;

class BaseRepository
{
    protected $model;

    public function setModel($obj): void
    {
        $this->model = resolve($obj);
    }

    public function all(?string $order_by = 'id'): mixed
    {
        return $this->model->orderBy($order_by)->get();
    }

    /**
     * @param  array<string|mixed>  $per_page
     */
    public function getPaginated(
        ?int $per_page = null
    ): mixed {
        return $this->model->paginate($per_page);
    }

    public function reset(mixed $obj): mixed
    {
        return $this->model = $obj;
    }

    public function findByColumn(
        string $column,
        string $value,
        array $with = [],
        string $model_name = 'Registro'
    ): mixed {
        $model = $this->model->with($with)->where($column, $value)->first();
        if (! $model) {
            throw new ModelNotFoundException('O '.$model_name.' não foi encontrado!');
        }

        return $model;
    }

    public function getByColumn($column, array $value, array $with = []): mixed
    {
        return $this->model->with($with)->whereIn($column, $value)->get();
    }

    /**
     * @param  array<string|mixed>  $attributes
     */
    public function update(string|int $id, array $attributes): object
    {
        return tap($this->model->findOrfail($id), function ($model) use ($attributes) {
            return $model->update($attributes);
        })->fresh();
    }

    public function findOrFail(int|string $id): ?object
    {
        return $this->model->findOrFail($id);
    }

    public function deleteByColumn($column, $value): bool
    {
        return $this->model->where($column, $value)->delete();
    }

    public function delete(string|int $id): bool
    {
        return $this->model->find($id)->delete();
    }

    public function find(
        int|string|null $id,
        array $with = [],
        string $model_name = 'Registro',
        bool $return_error = true
    ): mixed {
        $model = $this->model->with($with)->find($id);
        if (! $model && $return_error) {
            throw new ModelNotFoundException('O '.$model_name.' não foi encontrado(a)!');
        }

        return $model;
    }

    public function save($attributes): mixed
    {
        return $this->model->create($attributes);
    }

    /**
     * @param  array<string|mixed>  $attributes
     */
    public function create(array $attributes): mixed
    {
        if ($this->model instanceof Builder) {
            return $this->model->firstOrCreate($attributes);
        }

        return $this->model->firstOrCreate($attributes);
    }

    private function handlerAttributes($attributes): array
    {
        return $this->defineTimestamps($attributes);
    }

    private function defineTimestamps($attributes): array
    {
        if ($this->model->timestamps) {
            $date = $this->defineDatesByCasts($this->model->getCasts());
            $attributes = array_merge($attributes, [
                'created_at' => $date,
                'updated_at' => $date,
            ]);
        }

        return $attributes;
    }

    private function defineDatesByCasts($casts): Carbon|int
    {
        if (array_key_exists('created_at', $casts)) {
            if ($casts['created_at'] === 'datetime') {
                return now();
            }

            if ($casts['created_at'] === 'integer') {
                return time();
            }
        }

        return now();
    }
}
