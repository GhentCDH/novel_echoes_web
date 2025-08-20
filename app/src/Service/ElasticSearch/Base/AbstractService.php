<?php

namespace App\Service\ElasticSearch\Base;

use Elastica\Index;
use Exception;

abstract class AbstractService
{
    protected Client $client;
    protected ?Index $index = null;

    protected bool $debug = false;


    protected string $indexPrefix;

    public function __construct(
        Client $client,
        string $indexPrefix,
        bool $debug = false
    ) {
        $this->client = $client;
        $this->indexPrefix = $indexPrefix;
        $this->debug = $debug;

        if (!defined('static::indexName')) {
            throw new Exception('Constant indexName is not defined on subclass ' . get_class($this));
        }
    }

    protected function getClient(): Client
    {
        return $this->client;
    }

    protected function getIndex(): Index
    {
        if ( !$this->index ) {
            $this->index = $this->client->getIndex($this->getIndexName());
        }

        return $this->index;
    }

    public function removeIndex(string $indexName): void
    {
        $this->client->getIndex($indexName)->delete();
    }

    protected function getIndexName(): string
    {
        return $this->indexPrefix.'_'.static::indexName;
    }
}