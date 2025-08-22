<?php
namespace App\Resource\ElasticSearch;

class ElasticWorkResource extends ElasticBaseResource
{
    use TraitFilterPivot;
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request=null): array
    {
        $ret = $this->attributesToArray();
        $ret['locus'] = $this->pivot->locus ?? null;
        $ret['centuries'] = $this->filterPivot(ElasticBaseResource::collection($this->resource->centuries)->toArray());

        return $ret;
    }
}