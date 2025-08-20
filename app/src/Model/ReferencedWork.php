<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 *  @property string title
 */
class ReferencedWork extends AbstractModel implements IdLabelModelInterface
{
    public function getLabel(): string
    {
        return $this->title;
    }
}
