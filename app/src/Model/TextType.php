<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string name
 * @property string description
 */
class TextType extends AbstractModel implements IdLabelModelInterface
{
    public function getLabel(): string
    {
        return $this->name;
    }
}
