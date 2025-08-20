<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Class Work
 * @property string title
 */
class Work extends AbstractModel implements IdLabelModelInterface
{
    public function getLabel(): string
    {
        return $this->title;
    }

    public function centuries(): belongsToMany
    {
        return $this->belongsToMany(Century::class);
    }

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class);
    }
}
