<?php

namespace App\Model;

/**
 * @property string name
 * @property int order_num
 */
class Century extends AbstractModel implements IdLabelModelInterface
{
    public function getLabel(): string
    {
        return $this->name;
    }
}
