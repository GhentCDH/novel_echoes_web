<?php

namespace App\Service\ElasticSearch\Search;

use App\Helper\DibeQueryToElasticQuery;
use App\Service\ElasticSearch\Base\AbstractSearchService;

class TextSearchService extends AbstractSearchService
{
    const indexName = "text";
    private int $actorLimit = 3;

    protected function initSearchConfig(): array {
        $searchFilters = [
            'author' => [
                'type' => self::FILTER_OBJECT_ID,
                'field' => 'authors'
            ],

            'textType' => [
                'type' => self::FILTER_OBJECT_ID,
                'field' => 'textTypes'
            ],

            'works_nested' => [
                'type' => self::FILTER_NESTED_MULTIPLE,
                'nestedPath' => 'works',
                'filters' => [
                    'work' => [
                        'type' => self::FILTER_OBJECT_ID,
                        'field' => 'works'
                    ],
                    'century' => [
                        'type' => self::FILTER_OBJECT_ID,
                        'field' => 'works.centuries'
                    ],
                ]
            ],

            'reference' => [
                'type' => self::FILTER_OBJECT_ID,
                'field' => 'references'
            ],
            
        ];

        return $searchFilters;
    }

    protected function initAggregationConfig(): array {
        $searchFilters = $this->getSearchConfig();

        $aggregationFilters = [
            'author' => [
                'type' => self::AGG_OBJECT_ID_NAME,
                'field' => 'authors'
            ],

            'work' => [
                'type' => self::AGG_OBJECT_ID_NAME,
                'field' => 'works',
                'nestedPath' => 'works',
            ],

            'century' => [
                'type' => self::AGG_OBJECT_ID_NAME,
                'field' => 'works.centuries',
                'nestedPath' => 'works',
            ],

            'textType' => [
                'type' => self::AGG_OBJECT_ID_NAME,
                'field' => 'textTypes',
            ],

            'reference' => [
                'type' => self::AGG_OBJECT_ID_NAME,
                'field' => 'references',
            ],

        ];

        return $aggregationFilters;
    }

    protected function getDefaultSearchParameters(): array {
        return [
            'limit' => 25,
            'page' => 1,
            'ascending' => 1,
            'orderBy' => ['id'],
        ];
    }

    protected function sanitizeSearchResult(array $result): array
    {
        $returnProps = ['id', 'works', 'authors', 'centuries', 'references', 'locus'];

        $result = array_intersect_key($result, array_flip($returnProps));

        return $result;
    }

    protected function sanitizeSearchParameters(array $params, bool $merge_defaults = true): array
    {
        // convert orderBy field to elastic field expression
        if (isset($params['orderBy'])) {
            switch ($params['orderBy']) {
                case 'id':
                    $params['orderBy'] = [ $params['orderBy'] ];
                    break;
                case 'century':
                    $params['orderBy'] = [ 'works.centuries.order_num' ];
                    break;
                case 'author':
                    $params['orderBy'] = [ 'authors.name' ];
                    break;
                case 'work':
                    $params['orderBy'] = [ 'works.name' ];
                    break;
                case 'reference':
                    $params['orderBy'] = [ 'references.name' ];
                    break;
                default:
                    unset($params['orderBy']);
                    break;
            }
        }
        return parent::sanitizeSearchParameters($params);
    }
}
