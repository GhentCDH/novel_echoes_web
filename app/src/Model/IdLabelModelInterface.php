<?php

namespace App\Model;

interface IdLabelModelInterface
{
    public function getId(): int;
    public function getLabel(): string;
}
