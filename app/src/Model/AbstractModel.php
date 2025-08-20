<?php
namespace App\Model;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use ReflectionClass;
use ReflectionException;
use function Symfony\Component\String\u;

abstract class AbstractModel extends Model
{
    use TraitCustomModelSchema;

    protected $primaryKey;
    protected $table;

    public $timestamps = false;

    /**
     * BaseModel constructor.
     * - snake case table names
     * - tablename_id primary key
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->initSchema();
    }

    public function attributesToArray(): array
    {
        // hide primary key
        $this->makeHidden($this->getKeyName());

        // hide foreign keys of loaded relations
        foreach ($this->getRelations() as $relation) {
            if ( $relation instanceof Model) {
                $this->makeHidden($relation->getKeyName());
            }
        }

        // add 'id' attribute iso full primary key
        $mergedAttributes = [
            'id' => $this->getId()
        ];

        return array_merge($mergedAttributes, parent::attributesToArray() );
    }
}