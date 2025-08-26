<?php

namespace App\Service\ElasticSearch\Base;

use App\Resource\ResourceInterface;
use Elastica\Document;
use Illuminate\Http\Resources\Json\ResourceCollection;

interface IndexServiceInterface
{
    public function setup(): void;

    public function get(string $id): array|string;
    public function delete(string $id): void;
    public function deleteMultiple(array $ids): void;
    public function add(ResourceInterface $resource): void;
    public function update(ResourceInterface $resource): void;
    public function addMultiple(ResourceCollection $resources): void;
}
