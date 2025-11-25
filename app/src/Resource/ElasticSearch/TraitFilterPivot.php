<?php

namespace App\Resource\ElasticSearch;
trait TraitFilterPivot {
    // Filter out pivot data from many-to-many relationships
    public function filterPivot(array $data, array $allowedFields = ['text', 'locus', 'locus_order']): array {
        // collection? check for numerical index
        if (isset($data[0])) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->filterPivot($value);
            }
            return $data;
        }

        // single record then
        if (isset($data['pivot'])) {
            foreach ($data['pivot'] as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $data[$key] = $value;
                }
            }
            unset($data['pivot']);
        }

        return $data;
    }
}