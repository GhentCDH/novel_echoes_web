<?php

namespace App\Resource\ElasticSearch;

use App\Model\Text;
use App\Resource\Base\BaseResourceCollection;
use App\Resource\ResourceInterface;
use Illuminate\Http\Request;

/**
 * Class ElasticCharterResource
 * @package App\Resource
 * @mixin Text
 */
class ElasticTextResource extends ElasticBaseResource implements ResourceInterface
{
    public function toArray($request = null): array
    {
        /** @var Text $text */
        $text = $this->resource;

        $ret = $this->attributesToArray(true);
        $ret['authors'] = $this->filterPivot(ElasticBaseResource::collection($text->authors)->toArray());
        $ret['works'] = $this->filterPivot(ElasticWorkResource::collection($text->works)->toArray());
        $ret['textTypes'] = $this->filterPivot(ElasticBaseResource::collection($text->textTypes)->toArray());
        $ret['references'] = [];

        $ret['referencedGenres'] = $this->filterPivot(ElasticBaseResource::collection($text->referencedGenres)->toArray());
        $ret['referencedWorks'] = $this->filterPivot(ElasticBaseResource::collection($text->referencedWorks)->toArray());
        $ret['referencedPersons'] = $this->filterPivot(ElasticBaseResource::collection($text->referencedPersons)->toArray());

        $referenceMapping = [
            'referencedWorks' => 'work',
            'referencedPersons' => 'person',
            'referencedGenres' => 'genre',
        ];

        foreach($referenceMapping as $referenceStore => $referenceType) {
            foreach($ret[$referenceStore] as $reference) {
                print_r($reference);
                $ret['references'][] = [
                    "id" => $referenceType.":".$reference['id'],
                    "name" => $reference['name'],
                    'type' => $referenceType,
                    "locus" => $reference['locus'] ?? null,
                    "${referenceType}_id" => $reference['id'],
                    "id_name" => $referenceType.":".$reference['id_name'],
                ];
            }
        }

        return $ret;
    }

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
