<?php

namespace App\Service\ElasticSearch\Base;

interface SearchServiceInterface
{
    public function searchRaw(array $query = null): array;

    public function searchAndAggregate(array $query): array;

    public function search(array $query): array;

    public function aggregate(array $filters, ?array $configKeys = null): array;

    public function getSingle(string $id): array;
}
