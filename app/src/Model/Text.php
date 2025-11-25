<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use ReflectionException;


class Text extends AbstractModel
{
    protected $casts = [
    ];
    protected $with = [
    ];

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class);
    }

    public function works(): BelongsToMany
    {
        return $this->belongsToMany(Work::class)->withPivot('locus', 'locus_order');
    }

    public function textTypes(): BelongsToMany
    {
        return $this->belongsToMany(TextType::class);
    }

    public function referencedGenres(): BelongsToMany
    {
        return $this->belongsToMany(ReferencedGenre::class)->using(TextReferencedGenre::class)->withPivot('locus', 'text', 'locus_order');
    }

    public function referencedWorks(): BelongsToMany
    {
        return $this->belongsToMany(ReferencedWork::class)->withPivot('locus', 'text', 'locus_order');
    }

    public function referencedPersons(): BelongsToMany
    {
        return $this->belongsToMany(ReferencedPerson::class)->withPivot('locus', 'text', 'locus_order');
    }

}
