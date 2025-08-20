<?php


namespace App\Repository;

use Illuminate\Database\Eloquent\Builder;

interface IndexProviderInterface
{
    public function indexQuery(): Builder;
}