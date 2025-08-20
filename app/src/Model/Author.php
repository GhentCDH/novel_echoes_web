<?php

namespace App\Model;

/**
 * @property string name
 */
class Author extends AbstractModel implements IdLabelModelInterface
{
    public function getLabel(): string {
        return $this->name;
    }
}
