<?php


namespace App\Resource\Base;


use App\Model\AbstractModel;
use App\Resource\ResourceInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;
use JsonSerializable;

/**
 * @property AbstractModel $resource
 */
class BaseResource extends JsonResource implements ResourceInterface
{
    public function getId(): string
    {
        return $this->resource->getId();
    }

    /**
     * Transform the resource into a JSON array.
     *
     * @param  Request  $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request = null)
    {
        if (is_null($this->resource)) {
            return [];
        }

        return is_array($this->resource)
            ? $this->resource
            : $this->resource->toArray();
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */

    public function jsonSerialize(): array
    {
        return $this->resolve();
    }

    /**
     * Resolve the resource to an array.
     *
     * @param  Request|null  $request
     * @return array
     */
    public function resolve($request = null): array
    {
        $data = $this->toArray();

        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        } elseif ($data instanceof JsonSerializable) {
            $data = $data->jsonSerialize();
        }

        return $this->filter((array) $data);
    }

    /**
     * Create a new anonymous resource collection.
     *
     * @param  mixed  $resource
     * @return BaseResourceCollection
     */
    public static function collection($resource): BaseResourceCollection
    {
        return tap(new BaseResourceCollection($resource, static::class), function ($collection): void {
            if (property_exists(static::class, 'preserveKeys')) {
                $collection->preserveKeys = (new static([]))->preserveKeys === true;
            }
        });
    }
}