<?php


namespace App\Resource\ElasticSearch;

use App\Model\AbstractModel;
use App\Resource\Base\BaseResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ElasticBaseResource extends BaseResource
{
    public function __construct($resource)
    {
        // hide

        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @property AbstractModel resource
     * @return array
     */
    public function toArray($request=null)
    {
        if (!$this->resource) {
            return [];
        }

        $ret = parent::toArray(null);

        // add id_name if resource has name property
        if (method_exists($this->resource, 'getLabel')) {
            $ret['name'] = $this->resource->getLabel();
            $ret['id_name'] = $this->resource->getId().'_'.$this->resource->getLabel();
        }

        return $ret;
    }

    public function attributesToArray(bool $hideForeignKeys = false)
    {
        if (!$this->resource) {
            return [];
        }

        $ret = $this->resource->attributesToArray();

        // add id_name if resource has name property
        if (method_exists($this->resource, 'getLabel')) {
            $ret['name'] = $this->resource->getLabel();
            $ret['id_name'] = $this->resource->getId().'_'.$this->resource->getLabel();
        }

        return $ret;
    }

    protected static function boolean($value)
    {
        return $value ? 'true' : null;
    }
}