<?php
namespace App\Resource\ElasticSearch;

class ElasticWorkResource extends ElasticBaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request=null): array
    {
        $ret = $this->attributesToArray();
        $ret['centuries'] = ElasticBaseResource::collection($this->resource->centuries);

        return $ret;
    }
}