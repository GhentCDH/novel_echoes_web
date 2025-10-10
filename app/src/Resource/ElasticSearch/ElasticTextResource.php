<?php

namespace App\Resource\ElasticSearch;

use App\Model\Text;
use App\Resource\Base\BaseResourceCollection;
use App\Resource\ResourceInterface;
use Illuminate\Http\Request;

/**
 * Class ElasticTextResource
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
                    // todo: clean data before sorting is possible
                    //"sortLocus" => self::locusToInt($reference['locus'] ?? null),
                    "text" => $reference['text'] ?? null,
                    "${referenceType}_id" => $reference['id'],
                    "id_name" => $referenceType.":".$reference['id_name'],
                ];
            }
        }

        // sort references by name and sortLocus
        usort($ret['references'], fn($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? '') ?: ($a['sortLocus'] ?? 0) <=> ($b['sortLocus'] ?? 0));
//        usort($ret['references'], fn($a, $b) => ($a['sortLocus'] ?? 0) <=> ($b['sortLocus'] ?? 0));

        // sort shortcuts
        $ret['sortWorks'] = $ret['works'][0]['title'] ?? null;
        $ret['sortLocus'] = self::locusToInt($ret['works'][0]['locus'] ?? null);
        $ret['sortAuthors'] = $ret['authors'][0]['name'] ?? null;
        $ret['sortCenturies'] = array_map(fn($c) => $c['order_num'], $ret['works'][0]['centuries'] ?? []);
        $ret['sortReferences'] = array_map(fn($r) => trim($r['name'], "\ \n\r\t\v\0'"), $ret['references'] ?? []);
        return $ret;
    }

    // converts a text locus (004.012.003-004) into an integer (40012003), used for  sorting
    public static function locusToInt(?string $locus): ?int
    {
        if ($locus === null) {
            return null;
        }
        $locusParts = explode('.',explode('-', trim($locus))[0]); // take only the first part if there's a range
        if (count($locusParts) < 3) {
            return null;
        }
        return  ((int)$locusParts[0])*1000000 + ((int)$locusParts[1])*1000 + ((int)$locusParts[2]);
    }

}
