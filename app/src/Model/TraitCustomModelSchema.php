<?php
namespace App\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use ReflectionClass;
use ReflectionException;
use function Symfony\Component\String\u;

trait TraitCustomModelSchema {

    private function createJoinTableName(array $parts): string {
        return implode('__', $parts);
    }

    private function createPK(string $table): string {
        return $table . '_id';
    }

    private function createFK(string $table): string {
        return $table . '_id';
    }

    private function initSchema() {
        // default table name = class name converted to snake case
        if ( !$this->table ) {
            $parts = explode('_', (new ReflectionClass($this))->getShortName());
            $parts = array_map(function($part) {
                return u($part)->snake();
            }, $parts);
            $this->table = $this->createJoinTableName($parts);
        }
        // default primary key = tablename_id
        if ( !$this->primaryKey ) {
            $this->primaryKey = $this->createPK($this->table);
        }
    }

    /**
     * belongsTo
     * - foreign key name = primary key name or related table
     *
     * @param string $related
     * @param string|null $foreignKey
     * @param string|null $ownerKey
     * @param null $relation
     * @return BelongsTo
     * @throws ReflectionException
     */
    public function belongsTo($related, $foreignKey = null, $ownerKey = null, $relation = null): BelongsTo
    {
        $related_table = u((new ReflectionClass($related))->getShortName())->snake();

        if (is_null($foreignKey)) {
            $foreignKey = $this->createFK($related_table);
        }

        if (is_null($ownerKey)) {
            $ownerKey = $this->createPK($related_table);
        }

        return parent::belongsTo($related, $foreignKey, $ownerKey, $relation);
    }

    /**
     * belongsToMany
     * - foreign key names = primary key names or related tables
     * - join table name = table_name__related_table_name
     *
     * @param string $related
     * @param string|null $table
     * @param string|null $foreignPivotKey
     * @param string|null $relatedPivotKey
     * @param null $parentKey
     * @param null $relatedKey
     * @param null $relation
     * @return BelongsToMany
     * @throws ReflectionException
     */
    public function belongsToMany($related, $table = NULL, $foreignPivotKey = NULL, $relatedPivotKey = NULL, $parentKey = NULL, $relatedKey = NULL, $relation = NULL): BelongsToMany
    {
        $related_table = u((new ReflectionClass($related))->getShortName())->snake();
//        $related_pk = $this->createPK($related_table);

        if (is_null($table)) {
            $table = $this->createJoinTableName([$this->table, $related_table]);
        }

        if (is_null($foreignPivotKey)) {
            $foreignPivotKey = $this->getKeyName();
        }

        if (is_null($relatedPivotKey)) {
            $relatedPivotKey = $this->createFK($related_table);
        }
        return parent::belongsToMany($related, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relation);
    }

    /**
     * hasMany
     * - foreign key name = primary key name of current table
     *
     * @param string $related
     * @param string|null $foreignKey
     * @param string|null $localKey
     * @return HasMany
     */
    public function hasMany($related, $foreignKey = null, $localKey = null): HasMany
    {
        if (is_null($foreignKey)) {
            $foreignKey = $this->getKeyName();
        }

        if (is_null($localKey)) {
            $localKey = $this->getKeyName();
        }
        return parent::hasMany($related, $foreignKey, $localKey);
    }

    /**
     * @param string $related
     * @param string|null $foreignKey
     * @param string|null $localKey
     * @return HasOne
     */
    public function hasOne($related, $foreignKey = null, $localKey = null): HasOne
    {
        if (is_null($foreignKey)) {
            $foreignKey = $this->getKeyName();
        }

        if (is_null($localKey)) {
            $localKey = $this->getKeyName();
        }
        return parent::hasOne($related, $foreignKey, $localKey);
    }

    /**
     * Return primary key
     *
     * @return mixed
     */
    public function getId(): int
    {
        return $this->getKey();
    }

    /**
     * @param  string  $related
     * @param  string  $through
     * @param  string|null  $firstKey
     * @param  string|null  $secondKey
     * @param  string|null  $localKey
     * @param  string|null  $secondLocalKey
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function hasManyThrough($related, $through, $firstKey = null, $secondKey = null, $localKey = null, $secondLocalKey = null)
    {
        // todo:  check if this is correct
        $through_table = u((new ReflectionClass($through))->getShortName())->snake();
        $through_pk = $through_table.'_id';

        if (is_null($localKey)) {
            $localKey = $this->getKeyName();
        }

        return parent::hasManyThrough($related, $through, $localKey, $through_pk, $localKey, $through_pk);
    }

}