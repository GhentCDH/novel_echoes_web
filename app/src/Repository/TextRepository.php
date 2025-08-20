<?php


namespace App\Repository;


use App\Model\Text;
use Illuminate\Database\Eloquent\Builder;


class TextRepository extends AbstractRepository
{
    // auto load relations
    protected $relations = [
        'authors',
        'works', 'works.centuries',
        'textTypes',
        'referencedGenres',
        'referencedWorks',
        'referencedPersons',
    ];
    protected $model = Text::class;
}