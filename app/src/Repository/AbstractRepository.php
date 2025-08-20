<?php

namespace App\Repository;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class AbstractRepository implements RepositoryInterface
{
    protected $relations = [];
    protected $model;

    public function builder(): Builder
    {
        return $this->model::query();
    }

    // construct default query
    public function defaultQuery(): Builder
    {
        return $this->builder();
    }

    // constuct query to index all records
    public function indexQuery(): Builder
    {
        return $this->defaultQuery()->with($this->relations);
    }

    // find single record, with relations if needed
    public function find(int $id, $relations = []): ?Model
    {
        return $this->indexQuery()->with($relations)->find($id);
    }

    // find all records, with relations if needed
    public function findAll(int $limit = 0, $relations = []): Collection
    {
        return $this->indexQuery()->with($relations)->limit($limit)->get();
    }
}