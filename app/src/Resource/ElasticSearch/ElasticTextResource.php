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
    use TraitFilterPivot;
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

        $ret['title'] = $ret['works'][0]['title'] ?? 'Unknown work';
        if (isset($ret['works'][0]['locus']) && !empty($ret['works'][0]['locus'])) {
            $ret['title'] .= " (" . str_replace("0", "", $ret['works'][0]['locus']) .")";
        }

        foreach($referenceMapping as $referenceStore => $referenceType) {
            foreach($ret[$referenceStore] as $reference) {
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

        // sort shortcuts
        $ret['sortWorks'] = $ret['works'][0]['title'] ?? null;
        $ret['sortLocus'] = $ret['works'][0]['locus'] ?? null;
        $ret['sortAuthors'] = $ret['authors'][0]['name'] ?? null;
        $ret['sortCenturies'] = array_map(fn($c) => $c['order_num'], $ret['works'][0]['centuries'] ?? []);
        $ret['sortReferences'] = array_map(fn($r) => trim($r['name'], "\ \n\r\t\v\0'"), $ret['references'] ?? []);
        return $ret;
    }

}
