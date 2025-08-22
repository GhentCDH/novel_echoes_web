<?php

namespace App\Service\ElasticSearch\Index;

use App\Service\ElasticSearch\Base\AbstractIndexService;

class TextIndexService extends AbstractIndexService
{
    const indexName = "text";

    protected function getMappingProperties(): array {
        return [
            'text' => [
                'type' => 'text',
            ],
            'works' => [
                'type' => 'nested',
                'properties' => [
                    'name' => [
                        'type' => 'keyword',
                    ],
                ],
            ],
            'authors.name' => [
                'type' => 'keyword',
            ],
            'references.name' => [
                'type' => 'keyword',
            ],
            // references.id is a composite field (name + id), we must index it as a keyword
            // elasticsearch indexes the full string as a text field, but we need to be able to filter by id
            'references.id' => [
                'type' => 'keyword',
            ],
            'sortWorks' => [
                'type' => 'keyword',
            ],
            'sortAuthors' => [
                'type' => 'keyword',
            ],
            'sortCenturies' => [
                'type' => 'keyword',
            ],
            'sortReferences' => [
                'type' => 'keyword',
            ],
            'sortPivot' => [
                'type' => 'keyword',
            ],
        ];
    }

    protected function getIndexProperties(): array {
        return [
            'settings' => [
                'analysis' => [
                    "char_filter" => [
                        "remove_special" => [
                            "type" => "pattern_replace",
                            "pattern" => "[\\p{Punct}]",
                            "replacement" > "",
                        ],
                        "numbers_last" => [
                            "type" => "pattern_replace",
                            "pattern" => "([0-9])",
                            "replacement" => "zzz$1",
                        ],
                    ],
                    'normalizer' => [
                        'icu_normalizer' => [
                            "char_filter" => [
                                "remove_special",
                                "numbers_last",
                            ],
                            "filter" => [
                                "icu_folding",
                                "lowercase",
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}