<?php


namespace App\Service\ElasticSearch\Base;

use Elastica;
use Elastica\Index;

class Client extends Elastica\Client
{
    protected ?string $indexPrefix;

    public function __construct($config , ?string $indexPrefix = null)
    {
        $this->indexPrefix = $indexPrefix;
        parent::__construct($config);
    }

    public function getIndex(string $name): Index
    {
        return parent::getIndex(($this->indexPrefix ? $this->indexPrefix .'_' : '').$name );
    }
}