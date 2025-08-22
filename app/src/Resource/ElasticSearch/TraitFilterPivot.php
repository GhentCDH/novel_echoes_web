<?php

namespace App\Resource\ElasticSearch;
trait TraitFilterPivot {
    public function filterPivot(array $data) {
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
                if (in_array($key, ['text', 'locus'])) {
                    $data[$key] = $value;
                }
            }
            unset($data['pivot']);
        }

        return $data;
    }
}