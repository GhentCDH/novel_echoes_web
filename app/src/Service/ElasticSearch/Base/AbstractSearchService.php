<?php

namespace App\Service\ElasticSearch\Base;

use Elastica\Aggregation;
use Elastica\Query;
use Elastica\Query\AbstractQuery;


abstract class AbstractSearchService extends AbstractService implements SearchServiceInterface, SearchConfigInterface
{
    const MAX_AGG = 2147483647;
    const MAX_SEARCH = 10000;
    const SEARCH_RAW_MAX_RESULTS = 500;

    private SearchConfig $searchConfig;
    private AggregationConfig $aggregationConfig;

    public function __construct(Client $client, string $indexPrefix, bool $debug = false)
    {
        parent::__construct($client, $indexPrefix, $debug);

        // init search config
        $this->searchConfig = new SearchConfig();
        $this->searchConfig->setConfig($this->initSearchConfig());

        // init aggregation config
        $this->aggregationConfig = new AggregationConfig();
        $this->aggregationConfig->setConfig($this->initAggregationConfig());
    }

    /**
     * Add search filter details to search service
     * Return array of search_field => [
     *  'type' => aggregation type
     * ]
     * @return array
     */
    protected abstract function initSearchConfig(): array;

    /**
     * Add aggregation details to search service
     * Return array of aggregation_field => [
     *  'type' => aggregation type
     * ]
     * @return array
     */
    protected abstract function initAggregationConfig(): array;

    public function aggregate(array $filters, ?array $limitConfigKeys = null, ?array $excludeConfigKeys = null): array {
        $filters = $this->sanitizeSearchFilters($filters);
        return $this->_aggregate($filters, $limitConfigKeys, $excludeConfigKeys);
    }

    public function search(array $query): array
    {
        $query = $this->sanitizeQuery($query);
        return $this->_search($query);
    }

    public function searchAndAggregate(array $query, ?array $limitConfigKeys = null, ?array $excludeConfigKeys = null): array
    {
        // sanitize query
        $query = $this->sanitizeQuery($query);

        // search
        $result = $this->_search($query);

        // aggregate
        $result['aggregation'] = $this->_aggregate($query['filters'], $limitConfigKeys, $excludeConfigKeys);

        return $result;
    }

    public function getSingle(string $id): array
    {
        return $this->getIndex()->getDocument($id)->getData();
    }

    protected function sanitizeSearchParameters(array $params, bool $merge_defaults = true): array
    {
        // Set default parameters
        $defaults = $merge_defaults ? $this->getDefaultSearchParameters() : [];
        $result = array_intersect_key(
            $defaults,
            array_flip([
                'limit',
                'orderBy',
                'page',
                'ascending'
            ])
        );

        // Pagination
        if (isset($params['limit']) && is_numeric($params['limit'])) {
            $result['limit'] = intval($params['limit']);
        }
        if (isset($params['page']) && is_numeric($params['page'])) {
            $result['page'] = intval($params['page']);
        }

        // Sorting
        if (isset($params['orderBy'])) {
            $result['orderBy'] = is_array($params['orderBy']) ? $params['orderBy'] : [$params['orderBy']];
        }
        $result['ascending'] = true;
        if (isset($params['ascending'])) {
            $result['ascending'] = !in_array($params['ascending'], [0, '0', 'false', 'False'], true);
        }

        return $result;
    }

    protected function sanitizeSearchFilters(array $params): array
    {
        // Init Filters
        $filters = $this->getDefaultSearchFilters();

        // Add original values as _raw
        $filters['_raw'] = $params;

        // Validate values
        $filterConfigs = $this->getSearchConfig();

        foreach ($filterConfigs as $filterName => $filterConfig) {
            // filter has subfilters?
            if ($filterConfig['filters'] ?? false) {
                foreach ($filterConfig['filters'] as $subFilterName => $subFilterConfig) {
                    $ret = $this->sanitizeSearchFilter($subFilterName, $subFilterConfig, $params);
                    if (!is_null($ret)) {
                        $filters[$subFilterName] = $ret;
                    }
                }
            } else {
                // no subfilters
                $filterValue = $this->sanitizeSearchFilter($filterName, $filterConfig, $params);
                if (!is_null($filterValue)) {
                    $filters[$filterName] = $filterValue;
                }
            }
        }
        return $filters;
    }

    protected function sanitizeSearchFilter($filterName, $filterConfig, $queryValues): ?array
    {
        $ret = null;

        // get filter value
        // fixed value first -> filter value -> default value
        $queryKey = $filterConfig['filterName'] ?? $filterName; //todo: remove snake case!
        $filterValue = $filterConfig['value'] ?? $queryValues[$queryKey] ?? $filterConfig['defaultValue'] ?? null;

        switch ($filterConfig['type'] ?? self::DEFAULT_FILTER_TYPE) {
            case self::FILTER_NUMERIC:
            case self::FILTER_OBJECT_ID:
            case self::FILTER_NESTED_ID:
                if ($filterValue === null) break;
                if (is_array($filterValue)) {
                    $ret['value'] = array_map(fn($value) => $value, $filterValue);
                } elseif (is_numeric($filterValue)) {
                    $ret['value'] = [(int)$filterValue];
                } else {
                    $ret['value'] = [$filterValue];
                }
                $ret['operator'] = $queryValues[$filterName . '_op'] ?? ['or'];
                $ret['operator'] = is_array($ret['operator']) ? $ret['operator'] : [$ret['operator']];
                break;
            case self::FILTER_KEYWORD:
                if ($filterValue === null) break;
                $ret['value'] = is_array($filterValue) ? $filterValue : [ $filterValue ];
                $ret['operator'] = $queryValues[$filterName . '_op'] ?? ['or'];
                $ret['operator'] = is_array($ret['operator']) ? $ret['operator'] : [$ret['operator']];
                break;
            case self::FILTER_TEXT_PREFIX:
            case self::FILTER_KEYWORD_PREFIX:
                if ($filterValue === null) break;
                $ret['value'] = is_array($filterValue) ? $filterValue : [ $filterValue ];
                break;
            case self::FILTER_BOOLEAN:
                if ($filterValue === null) break;
                $ret['value'] = ($filterValue === '1'
                    || $filterValue === 'true'
                    || is_array($filterValue) && (in_array('1', $filterValue, true) || in_array('true', $filterValue, true) )
                );
                break;
            case self::FILTER_EXISTS:
                if ($filterValue === null) break;
                if ($filterValue === 'true') {
                    $ret['value'] = true;
                }
                break;
            case self::FILTER_DATE_RANGE:
                $rangeFilter = [];

                $valueField = $filterConfig['floorField'];
                if (isset($queryValues[$valueField]) && is_numeric($queryValues[$valueField])) {
                    $rangeFilter['floor'] = $queryValues[$valueField];
                }

                $valueField = $filterConfig['ceilingField'];
                if (isset($queryValues[$valueField]) && is_numeric($queryValues[$valueField])) {
                    $rangeFilter['ceiling'] = $queryValues[$valueField];
                }

                $valueField = $filterConfig['typeField'];
                if (isset($queryValues[$valueField]) && in_array($queryValues[$valueField], ['exact', 'included', 'include', 'overlap'], true)) {
                    $rangeFilter['type'] = $queryValues[$valueField];
                }

                if ($rangeFilter) {
                    $ret['value'] = $rangeFilter;
                }

                break;
            case self::FILTER_DMY_RANGE:
                $rangeFilter = [
                    'from' => [],
                    'till' => [],
                    'hasFrom' => false,
                    'hasTill' => false
                ];
                $boolValid = false;

                $dateParts = [
                    'year',
                    'month',
                    'day',
                ];

                foreach ($dateParts as $datePart) {
                    $rangeFilter['from'][$datePart] = is_numeric($filterValue['from'][$datePart] ?? null) ? intval($filterValue['from'][$datePart]) : null;
                    $rangeFilter['hasFrom'] = $rangeFilter['hasFrom'] || ($rangeFilter['from'][$datePart] !== null);
                    $rangeFilter['till'][$datePart] = is_numeric($filterValue['till'][$datePart] ?? null) ? intval($filterValue['till'][$datePart]) : null;
                    $rangeFilter['hasTill'] = $rangeFilter['hasTill'] || ($rangeFilter['till'][$datePart] !== null);
                }

                // check if valid range query (valid combinations: year, year-month, year-month-day)
                if ($rangeFilter['hasFrom'] && $rangeFilter['hasTill'] &&
                    in_array(
                        implode('-', array_keys(array_filter($rangeFilter['from'], fn($key,$value) => $value !== null, ARRAY_FILTER_USE_BOTH))),
                        ['year-month-day', 'year-month', 'year'],
                        true
                    ) &&
                    in_array(
                        implode('-', array_keys(array_filter($rangeFilter['till'], fn($key,$value) => $value !== null, ARRAY_FILTER_USE_BOTH))),
                        ['year-month-day', 'year-month', 'year'],
                        true
                    )
                ) {
                    $rangeFilter['type'] = 'range';
                    $boolValid = true;
                } elseif ($rangeFilter['hasFrom']) {
                    $rangeFilter['type'] = 'exact';
                    $boolValid = true;
                } else {
                    $rangeFilter['type'] = 'invalid';
                }

                if ($boolValid) {
                    $ret['value'] = $rangeFilter;
                }

                break;
            case self::FILTER_NUMERIC_RANGE_SLIDER:
                $rangeFilter = [];
                $ignore = $filterConfig['ignore'] ?? [];
                $ignore = is_array($ignore) ? $ignore : [$ignore];

                $value = $filterValue[0] ?? null;
                if (is_numeric($value) && !in_array(floatval($value), $ignore)) {
                    $rangeFilter['floor'] = floatval($value);
                }

                $value = $filterValue[1] ?? null;
                if (is_numeric($value) && !in_array(floatval($value), $ignore)) {
                    $rangeFilter['ceiling'] = floatval($value);
                }

                if ($rangeFilter) {
                    $ret['value'] = $rangeFilter;
                }

                break;
            case self::FILTER_TEXT:
                if ($filterValue === null) break;
                if (is_array($filterValue)) {
                    $ret['value'] = $filterValue;
                }
                if (is_string($filterValue) && $filterValue !== '') {
                    $combination = $queryValues[$filterName . '_combination'] ?? 'any';
                    $combination = in_array($combination, ['any', 'all', 'phrase'], true) ? $combination : 'any';

                    $ret['value'] = [
                        'text' => $filterValue,
                        'combination' => $combination
                    ];
                }
                break;
            default:
                if ($filterValue === null) break;
                if (is_string($filterValue)) {
                    $ret['value'] = $filterValue;
                }
                if (is_array($filterValue)) {
                    $ret['value'] = $filterValue;
                }
                break;
        }
        return $ret;
    }

    protected function sanitizeQuery(array $query): array
    {
        $result = $this->sanitizeSearchParameters($query);
        $result['filters'] = $this->sanitizeSearchFilters($query['filters'] ?? []);

        return $result;
    }

    private function sanitizeTermAggregationItems(array $items, array $aggConfig, array $aggFilterValues): array
    {
        $must = [];
        $may = [];
        $output = [];
        foreach($items as $item) {
            $label = $item['name'];
            $item['active'] = $item['active'] ?? false;

            // allowedValue?
            if (count($aggConfig['allowedValue'] ?? []) && !in_array($item['id'], $aggConfig['allowedValue'], true)) {
                continue;
            }
            // ignoreValue?
            if (count($aggConfig['ignoreValue'] ?? []) && in_array($item['id'], $aggConfig['ignoreValue'], true)) {
                continue;
            }
            // zero doc count? only allow if value in search filter values
            if ( $item['count'] === 0 && ( !count($aggFilterValues) || !in_array($item['id'], $aggFilterValues, true) ) ) {
                continue;
            }
            // replace label?
            if ( $aggConfig['replaceLabel'] ?? null ) {
                $item['name'] = $label = str_replace($aggConfig['replaceLabel']['search'], $aggConfig['replaceLabel']['replace'], $label);
            }
            if ($aggConfig['mapLabel'] ?? null ) {
                $item['name'] = $label = $aggConfig['mapLabel'][$label] ?? $item['name'];
            }

            if ($item['active']) {
                $must[] = $item;
            } else {
                $may[] = $item;
            }
        }

        // safe limit?
        if ($aggConfig['safeLimit']) {
            $may = array_slice($may, 0, $aggConfig['safeLimit']);
        }

        $output = array_merge($must, $may);

        // sort?
        $this->sortAggregationResult($output, $aggConfig);
        return $output;
    }

    protected function getDefaultSearchFilters(): array
    {
        return [];
    }

    protected function getDefaultSearchParameters(): array
    {
        return [];
    }

    protected function onBeforeSearch(array &$searchParams, Query $query, Query\FunctionScore $queryFS): void {
    }

    protected function onInitAggregationConfig(array &$arrAggregationConfigs, array $arrFilterValues): void {
    }

    public final function getAggregationConfig(): array
    {
        return $this->aggregationConfig->getConfig();
    }

    public final function getSearchConfig(): array
    {
        return $this->searchConfig->getConfig();
    }

    protected function createSearchQuery(array $filterValues, ?array $filterConfigs = null): Query\BoolQuery
    {
        $filterConfigs = $filterConfigs ?? $this->getSearchConfig();

        // create parent query
        $query = new Query\BoolQuery();

        // walk filter configs
        foreach ($filterConfigs as $filterConfig) {
            $this->addFieldQuery($query, $filterConfig, $filterValues);
        }

        return $query;
    }

    private function calculateFilterField($config): ?string
    {
        $filterField = $config['field'] ?? null;
        if (!$filterField) {
            return null;
        }

        return $filterField;
    }

    protected function addFieldQuery(Query\BoolQuery $query, array $filterConfig, array $filterValues): void
    {
        $query_top = $query;

        // nested filter?
        $boolIsNestedFilter = SearchConfig::isNestedFilter($filterConfig);

        $filterName = $filterConfig['name'];
        $filterField = $this->calculateFilterField($filterConfig);
        // values are sanitized already, line below is not needed
        // $filterValue = $filterConfig['value'] ?? $filterValues[$filterName]['value'] ?? $filterConfig['defaultValue'] ?? null; // filter can have fixed value, query value or default value
        $filterValue = $filterValues[$filterName]['value'] ?? null;
        $filterType = $filterConfig['type'];
        $filterNestedPath = $filterConfig['nestedPath'] ?? null;

        // skip filter if no filter value and no subfilters
        if (!isset($filterConfig['filters']) && !$filterValue ) {
            return;
        }

        // add filter based on type
        switch ($filterType) {
            case self::FILTER_OBJECT_ID:
            case self::FILTER_NESTED_ID:
            case self::FILTER_KEYWORD:
            case self::FILTER_NUMERIC:
                $arrSuffix = [
                    self::FILTER_OBJECT_ID => '.id',
                    self::FILTER_NESTED_ID => '.id',
                    self::FILTER_KEYWORD => '.keyword',
                ];

                $filterFieldId = $filterField . ($arrSuffix[$filterType] ?? '');
                $filterFieldCount = $filterField . '_count';
                // todo: default filter operator is hard coded, should make this configuration?
                $filterOperator = $filterValues[$filterName]['operator'] ?? ['or'];

                $boolIsNone = in_array((int) $filterConfig['noneKey'], $filterValue, true)
                    || in_array($filterConfig['noneKey'], $filterValue, true)
                    || in_array('none', $filterOperator, true);

                if ($boolIsNone) {
                    if ($boolIsNestedFilter) {
                        $queryNested = self::createNestedQuery($filterNestedPath, $filterConfig);
                        $query_filters = $queryNested->getParam('query');
                        $query->addFilter($queryNested);
                    } else {
                        $query_filters = $query;
                    }
                    $query_filters->addMustNot(new Query\Exists($filterFieldId));
                    break;
                }

                $boolIsAny = in_array((int) $filterConfig['anyKey'], $filterValue, true)
                    || in_array($filterConfig['anyKey'], $filterValue, true)
                    || in_array('any', $filterOperator, true);
                if ($boolIsAny) {
                    if ($boolIsNestedFilter) {
                        $queryNested = self::createNestedQuery($filterNestedPath, $filterConfig);
                        $query_filters = $queryNested->getParam('query');
                        $query->addFilter($queryNested);
                    } else {
                        $query_filters = $query;
                    }
                    $query_filters->addMust(new Query\Exists($filterFieldId));
                    break;
                }

                // AND operator? or default OR operator?
                if (in_array('and', $filterOperator, true)) {
                    $query = new Query\BoolQuery();

                    foreach ($filterValue as $value) {
                        // nested query? create nested query for each value
                        if ($boolIsNestedFilter) {
                            // create nested query
                            $queryNested = self::createNestedQuery($filterNestedPath, $filterConfig);
                            $query_filters = $queryNested->getParam('query');
                            // add nested query to main query, basted on operator
                            $query->addFilter($queryNested);
                        } else {
                            $query_filters = $query;
                        }

                        $query_filters->addMust(self::createTermQuery($filterFieldId, $value));
                    }
                    if ($query->count()) {
                        in_array('not', $filterOperator, true) ? $query_top->addMustNot($query) : $query_top->addFilter($query);
                        // only these allowed?
                        // todo: what if count field inside nested?
                        if (in_array('only', $filterOperator, true)) {
                            $query_top->addMust((new Query\Term())->setTerm($filterFieldCount, count($filterValue)));
                        }
                    }
                } else {
                    // nested query?
                    if ($boolIsNestedFilter) {
                        // create nested query
                        $query = self::createNestedQuery($filterNestedPath, $filterConfig);
                        $query_filters = $query->getParam('query');
                    } else {
                        $query_filters = $query = new Query\BoolQuery();
                    }

                    $query_filters->addShould(new Query\Terms($filterFieldId, $filterValue));

                    if ($query_filters->count()) {
                        in_array('not', $filterOperator, true) ? $query_top->addMustNot($query) : $query_top->addFilter($query);
                        // allow
                        if (in_array('only', $filterOperator, true)) {
                            $query_top->addMust((new Query\Term())->setTerm($filterFieldCount, count($filterValue)));
                        }
                    }
                }

                break;
            case self::FILTER_TEXT_PREFIX:
                if ( $filterValue ) {
                    $filterQuery = new Query\MatchPhrasePrefix();
                    $filterQuery->setFieldQuery($filterField, $filterValue[0]);
                    $query->addMust($filterQuery);
                }
                break;
            case self::FILTER_KEYWORD_PREFIX:
                if ( $filterValue ) {
                    $filterQuery = [
                        'match_bool_prefix' => [
                            $filterField => [
                                'query' => $filterValue[0]
                            ]
                        ]
                    ];
                    $query->addMust($filterQuery);
                }
                break;
            case self::FILTER_EXISTS:
                if ( $filterValue ) {
                    $filterQuery = new Query\Exists($filterField);               
                    $query->addMust($filterQuery);
                }
                break;
            case self::FILTER_BOOLEAN:
                if ($filterConfig['onlyFilterIfTrue'] ?? false) {
                    if ($filterValue) {
                        $filterQuery = new Query\Term();
                        $filterQuery->setTerm($filterField, $filterValue ? $filterConfig['trueValue'] ?? true : $filterConfig['falseValue'] ?? false);

                        $query->addMust($filterQuery);
                    }
                } else {
                    $filterQuery = new Query\Term();
                    $filterQuery->setTerm($filterField, $filterValue ? $filterConfig['trueValue'] ?? true : $filterConfig['falseValue'] ?? false);

                    $query->addMust($filterQuery);
                }
                break;
            case self::FILTER_WILDCARD:
                $filterQuery = new Query\Wildcard($filterField, $filterValue);
                $query->addMust($filterQuery);
                break;
            case self::FILTER_TEXT:
                $query->addMust(self::constructTextQuery($filterField, $filterValue, $filterConfig));
                break;
            case self::FILTER_QUERYSTRING:
                $query->addMust((new Query\QueryString($filterValue))->setDefaultField($filterField)->setAnalyzeWildcard());
            case self::FILTER_NUMERIC_RANGE_SLIDER:
                $floorField = $filterConfig['floorField'] ?? $filterConfig['field'] ?? $filterName;
                $ceilingField = $filterConfig['ceilingField'] ?? $filterConfig['field'] ?? $filterName;

                if (isset($filterValue['floor'])) {
                    $query->addMust(
                        (new Query\Range())
                            ->addField($floorField, ['gte' => $filterValue['floor']])
                    );
                }
                if (isset($filterValue['ceiling'])) {
                    $query->addMust(
                        (new Query\Range())
                            ->addField($ceilingField, ['lte' => $filterValue['ceiling']])
                    );
                }
                break;
            case self::FILTER_DMY_RANGE:
                if (($filterValue['type'] ?? null) === 'exact') {
                    foreach ($filterValue['from'] as $datePart => $value) {
                        // todo: add datepart field option
                        if ($value) {
                            $query->addMust(
                                (new Query\Term())->setTerm($filterField . '.' . $datePart, $value)
                            );
                        }
                    }
                }
                if (($filterValue['type'] ?? null) === 'range') {
                    $dateParts = ['year', 'month', 'day'];

                    $count = count(array_filter($filterValue['from']));
                    $fromCarry = [];
                    $tillCarry = [];
                    $i = 0;

                    $fromQuery = new Query\BoolQuery();
                    $tillQuery = new Query\BoolQuery();

                    foreach ($dateParts as $datePart) {
                        if (!is_null($filterValue['from'][$datePart] ?? null) && !is_null($filterValue['till'][$datePart] ?? null)) {
                            $fromQueryPart = new Query\BoolQuery();
                            $tillQueryPart = new Query\BoolQuery();

                            $i++;
                            foreach ($fromCarry as $carryDatePart) {
                                $fromQueryPart->addMust((new Query\Term())->setTerm($filterField . '.' . $carryDatePart, $filterValue['from'][$carryDatePart]));
                                $tillQueryPart->addMust((new Query\Term())->setTerm($filterField . '.' . $carryDatePart, $filterValue['till'][$carryDatePart]));
                            }
                            $operator = ($i === $count) ? 'gte' : 'gt';
                            $fromQueryPart->addMust((new Query\Range())->addField($filterField . '.' . $datePart, [$operator => $filterValue['from'][$datePart]]));
                            $operator = ($i === $count) ? 'lte' : 'lt';
                            $tillQueryPart->addMust((new Query\Range())->addField($filterField . '.' . $datePart, [$operator => $filterValue['till'][$datePart]]));
                            $fromCarry[] = $datePart;

                            $fromQuery->addShould($fromQueryPart);
                            $tillQuery->addShould($tillQueryPart);
                        }
                    }

                    if ($fromQuery->count()) {
                        $query->addMust($fromQuery);
                    }
                    if ($tillQuery->count()) {
                        $query->addMust($tillQuery);
                    }
                    /*
                    year > from[year] or
                    OR ( year == from[year] and ( month > from[month] )
                    OR ( year == from[year] and ( month == from[month] ) and ( day >= from[day] )

*/
                }
                break;

            case self::FILTER_DATE_RANGE:
                // The data interval must exactly match the search interval
                if (isset($filterValue['type']) && $filterValue['type'] == 'exact') {
                    if (isset($filterValue['floor'])) {
                        $query->addMust(
                            self::createTermQuery($filterConfig['floorField'], $filterValue['floor'])
//                            new Query\Match($filterConfig['floorField'], $filterValue['floor'])
                        );
                    }
                    if (isset($filterValue['ceiling'])) {
                        $query->addMust(
                            self::createTermQuery($filterConfig['ceilingField'], $filterValue['ceiling'])
//                            new Query\Match($filterConfig['ceilingField'], $filterValue['ceiling'])
                        );
                    }
                }

                // The data interval must be included in the search interval
                if (isset($filterValue['type']) && $filterValue['type'] == 'included') {
                    if (isset($filterValue['floor'])) {
                        $query->addMust(
                            (new Query\Range())
                                ->addField($filterConfig['floorField'], ['gte' => $filterValue['floor']])
                        );
                    }
                    if (isset($filterValue['ceiling'])) {
                        $query->addMust(
                            (new Query\Range())
                                ->addField($filterConfig['ceilingField'], ['lte' => $filterValue['ceiling']])
                        );
                    }
                }

                // The data interval must include the search interval
                // If only start or end: exact match with start or end
                // range must be between floor and ceiling
                if (isset($filterValue['type']) && $filterValue['type'] == 'include') {
                    if (isset($filterValue['floor']) && isset($filterValue['ceiling'])) {
                        $query->addMust(
                            (new Query\Range())
                                ->addField($filterConfig['floorField'], ['lte' => $filterValue['floor']])
                        );
                        $query->addMust(
                            (new Query\Range())
                                ->addField($filterConfig['ceilingField'], ['gte' => $filterValue['ceiling']])
                        );
                    }
                }
                // The data interval must overlap with the search interval
                // floor or ceiling must be within range, or range must be between floor and ceiling
                if (isset($filterValue['type']) && $filterValue['type'] == 'overlap') {
                    $args = [];
                    if (isset($filterValue['floor'])) {
                        $args['gte'] = $filterValue['floor'];
                    }
                    if (isset($filterValue['ceiling'])) {
                        $args['lte'] = $filterValue['ceiling'];
                    }
                    $subQuery = (new Query\BoolQuery())
                        // floor
                        ->addShould(
                            (new Query\Range())
                                ->addField(
                                    $filterConfig['floorField'],
                                    $args
                                )
                        )
                        // ceiling
                        ->addShould(
                            (new Query\Range())
                                ->addField(
                                    $filterConfig['ceilingField'],
                                    $args
                                )
                        );
                    if (isset($filterValue['floor']) && isset($filterValue['ceiling'])) {
                        $subQuery
                            // between floor and ceiling
                            ->addShould(
                                (new Query\BoolQuery())
                                    ->addMust(
                                        (new Query\Range())
                                            ->addField($filterConfig['floorField'], ['lte' => $filterValue['floor']])
                                    )
                                    ->addMust(
                                        (new Query\Range())
                                            ->addField($filterConfig['ceilingField'], ['gte' => $filterValue['ceiling']])
                                    )
                            );
                    }
                    $query->addMust(
                        $subQuery
                    );
                }
                break;

            case self::FILTER_NESTED_MULTIPLE:
                // add subfilters with values
                // todo: this is wrong no? filter can have default value, should not be left out
//                $subFilters = array_intersect_key($filterConfig['filters'] ?? [], $filterValues);
//                if (count($subFilters)) {
//                    // create nested query
//                    $queryNested = self::createNestedQuery($filterNestedPath, $filterConfig);
//                    $subQuery = $queryNested->getParam('query');
//
//                    foreach ($subFilters as $subFilterName => $subFilterConfig) {
//                        $this->addFieldQuery($subQuery, $subFilterConfig, $filterValues);
//                    }
//
//                    if ($subQuery->count()) {
//                        $query->addMust($queryNested);
//                    }
//                }

                $queryNested = self::createNestedQuery($filterNestedPath, $filterConfig);
                $subQuery = $this->createSearchQuery($filterValues, $filterConfig['filters']);

                // count number of inner hits using match_all query?
                // with match_all, each result gets same boost score (default 1)
                if ( ($filterConfig['scoreEqual'] ?? false) ) {
                    $subQuery->addMust( new Query\MatchAll() );
                    // todo: set $filterConfig['boost'] ?? 1
                }

                if ($subQuery->count()) {
                    $queryNested->setQuery($subQuery);
                    $query->addMust($queryNested);
                }

                break;
            case self::BOOL_QUERY_OR:
//                dump($filterConfig);
//                dump($filterValues);
                $queryOR = new Query\BoolQuery();
                foreach($filterConfig['filters'] as $subFilterConfig) {
                    $querySUB = new Query\BoolQuery();
                    $this->addFieldQuery($querySUB, $subFilterConfig, $filterValues);
                    if ($querySUB->count()) {
                        $queryOR->addShould($querySUB);
                    }
                }
//                dump($queryOR);
                if ($queryOR->count()) {
                    $query->addMust($queryOR);
                }

                break;
            case self::BOOL_QUERY_AND:
//                dump($filterConfig);
//                dump($filterValues);
                $queryAND = new Query\BoolQuery();
                foreach($filterConfig['filters'] as $subFilterConfig) {
                    $this->addFieldQuery($queryAND, $subFilterConfig, $filterValues);
                }
//                dump($queryAND);
                if ($queryAND->count()) {
                    $query->addMust($queryAND);
                }

                break;
        }
    }

    private static function createNestedQuery(string $filterNestedPath, array $filterConfig = []): Query\Nested
    {
        // create nested query
        $queryNested = (new Query\Nested())
            ->setPath($filterNestedPath)
            ->setQuery(new Query\BoolQuery());

        // add inner hits?
        if ($filterConfig['innerHits'] ?? false) {
            $innerHits = new Query\InnerHits();
            if ($filterConfig['innerHits']['size'] ?? false) {
                $innerHits->setSize($filterConfig['innerHits']['size']);
            }
            if ($filterConfig['innerHits']['name'] ?? false) {
                $innerHits->setName($filterConfig['innerHits']['name']);
            }
            $queryNested->setInnerHits($innerHits);
        }

        // score mode
        if ($filterConfig['scoreMode'] ?? false) {
            $queryNested->setParam('score_mode', $filterConfig['scoreMode']);
        }

        return $queryNested;
    }

    private static function createTermQuery(string $field, $value): Query\Term
    {
        return (new Query\Term())->setTerm($field, $value);
    }

    /**
     * Construct a text query
     * @param string $key Elasticsearch field to match (unless $value['field']) is provided
     * @param array $value Array with [combination] of match (any, all, phrase), the [text] to search for and optionally the [field] to search in (if not provided, $key is used)
     * @return AbstractQuery
     */
    protected static function constructTextQuery(string $field, array $value, array $filterConfig): AbstractQuery
    {
        // Replace multiple spaces with a single space
        $text = preg_replace('!\s+!', ' ', $value['text']);
        $combination = $value['combination'] ?? 'any';

        // Remove colons
        $text = str_replace(':', '', $text);

        // Check if user does not use advanced syntax
        if (preg_match('/AND|OR|[\/~\-"()]/', $text) === 0) {
            if ($combination == 'phrase') {
                if (preg_match('/[*?]/', $text) === 0) {
                    $text = '"' . $text . '"';
                } else {
                    $text = implode(' AND ', explode(' ', $text));
                }
            } elseif ($combination == 'all') {
                $text = implode(' AND ', explode(' ', $text));
            }
        }

        return (new Query\QueryString($text))->setDefaultField($field);
    }

    protected function _search(array $params = null): array
    {
        // sanitize search parameters
        $searchParams = $params;

        // Construct query
        $queryFS = new Query\FunctionScore();
        $query = new Query($queryFS);

        // onBeforeSearch
        $this->onBeforeSearch($searchParams, $query, $queryFS);
        $this->debug && dump($searchParams);

        // Number of results
        if (isset($searchParams['limit']) && is_numeric($searchParams['limit'])) {
            $query->setSize($searchParams['limit']);
        }

        // Pagination
        if (isset($searchParams['page']) && is_numeric($searchParams['page']) &&
            isset($searchParams['limit']) && is_numeric($searchParams['limit'])
        ) {
            $query->setFrom(($searchParams['page'] - 1) * $searchParams['limit']);
        }

        // Sorting
        if (isset($searchParams['orderBy'])) {
            $order = $searchParams['ascending'] ? 'asc' : 'desc';
            $sort = [];
            foreach ($searchParams['orderBy'] as $field) {
                $sort[] = [$field => $order];
            }
            $query->setSort($sort);
        }

        // Track total number of hits
        $query->setTrackTotalHits();

        // Filtering
        $searchFilters = $params['filters'] ?? [];
        if (count($searchFilters)) {
            $this->debug && dump($searchFilters);
            $queryFS->setQuery($this->createSearchQuery($searchFilters));
            $query->setHighlight($this->createHighlight($searchFilters));
        } else {
            $queryFS->setQuery( new Query\MatchAll() );
        }

        $this->debug && dump(json_encode($query->toArray(), JSON_PRETTY_PRINT));

        // Search
        $data = $this->getIndex()->search($query)->getResponse()->getData();

        // Format response
        $response = [
            'count' => $data['hits']['total']['value'] ?? 0,
            'data' => [],
            'search' => $searchParams,
            'filters' => $searchFilters
        ];

        // Build array to remove _stemmer or _original blow
        $rename = [];
        $filterConfigs = $this->getSearchConfig();
        foreach ($filterConfigs as $filterName => $filterConfig) {
            switch ($filterConfig['type'] ?? null) {
                case self::FILTER_TEXT:
                    $filterValue = $filterValues[$filterName] ?? null;
                    if (isset($filterValue['field'])) {
                        $rename[$filterValue['field']] = explode('_', $filterValue['field'])[0];
                    }
                    break;
            }
        }
        foreach (($data['hits']['hits'] ?? []) as $result) {
            $part = $result['_source'];
            $part['_score'] = $result['_score'];
            if (isset($result['highlight'])) {
                foreach ($result['highlight'] as $key => $value) {
                    $part['original_' . $key] = $part[$key];
                    $part[$key] = self::formatHighlight($value[0]);
                }
            }
            // Remove _stemmer or _original
            foreach ($rename as $key => $value) {
                if (isset($part[$key])) {
                    $part[$value] = $part[$key];
                    unset($part[$key]);
                }
                if (isset($part['original_' . $key])) {
                    $part['original_' . $value] = $part['original_' . $key];
                    unset($part['original_' . $key]);
                }
            }

            // add inner_hits
            if (isset($result['inner_hits'])) {
                $part['inner_hits'] = [];
                foreach ($result['inner_hits'] as $field_name => $inner_hit) {
                    $values = [];
                    foreach ($inner_hit['hits']['hits'] ?? [] as $hit) {
                        if ($hit['_source'] ?? false) {
                            $values[] = $hit['_source'];
                        }
                    }
                    $count = $inner_hit['hits']['total']['value'] ?? null;
                    $part['inner_hits'][$field_name] = [ 'data' => $values, 'count' => $count ];
                }
            }

            // sanitize result
            $response['data'][] = $this->sanitizeSearchResult($part);
        }

        return $response;
    }

    public function searchRaw(array $params = null, array $fields = null): array
    {
        // sanitize search parameters
        $searchParams = $this->sanitizeSearchParameters($params, false);

        // Construct query
        $query = new Query();

        // Number of results
        if (isset($searchParams['limit']) && is_numeric($searchParams['limit'])) {
            $query->setSize(min($searchParams['limit'], static::SEARCH_RAW_MAX_RESULTS)); //todo; fix this!
        } else {
            $query->setSize(static::SEARCH_RAW_MAX_RESULTS);
        }

        // Pagination
        if (isset($searchParams['page']) && is_numeric($searchParams['page']) &&
            isset($searchParams['limit']) && is_numeric($searchParams['limit'])
        ) {
            $query->setFrom(($searchParams['page'] - 1) * $searchParams['limit']);
        }

        // Sorting
        if (isset($searchParams['orderBy'])) {
            $order = $searchParams['ascending'] ? 'asc' : 'desc';
            $sort = [];
            foreach ($searchParams['orderBy'] as $field) {
                $sort[] = [$field => $order];
            }
            $query->setSort($sort);
        }

        // Set result fields
        // todo: better use fields option?
        if ($fields) {
            $query->setSource($fields);
        }

        // Filtering
        $searchFilters = $this->sanitizeSearchFilters($params['filters'] ?? []);
        if (count($searchFilters)) {
            $searchQuery = $this->createSearchQuery($searchFilters);
            $query->setQuery($searchQuery);
        }

        // Search
        $data = $this->getIndex()->search($query)->getResponse()->getData();

        // Format response
        $response = [
            'count' => $data['hits']['total']['value'] ?? 0,
            'data' => [],
        ];

        // Build array to remove _stemmer or _original blow
        $rename = [];
        $filterConfigs = $this->getSearchConfig();
        foreach ($filterConfigs as $filterName => $filterConfig) {
            switch ($filterConfig['type'] ?? null) {
                case self::FILTER_TEXT:
                    $filterValue = $filterValues[$filterName] ?? null;
                    if (isset($filterValue['field'])) {
                        $rename[$filterValue['field']] = explode('_', $filterValue['field'])[0];
                    }
                    break;
            }
        }
        foreach (($data['hits']['hits'] ?? []) as $result) {
            $part = $result['_source'];
            $part['_score'] = $result['_score'];

            // Remove _stemmer or _original
            foreach ($rename as $key => $value) {
                if (isset($part[$key])) {
                    $part[$value] = $part[$key];
                    unset($part[$key]);
                }
                if (isset($part['original_' . $key])) {
                    $part['original_' . $value] = $part['original_' . $key];
                    unset($part['original_' . $key]);
                }
            }

            // add inner_hits
            if (isset($result['inner_hits'])) {
                $part['inner_hits'] = [];
                foreach ($result['inner_hits'] as $field_name => $inner_hit) {
                    $values = [];
                    foreach ($inner_hit['hits']['hits'] as $hit) {
                        $values[] = $hit['_source'];
                    }
                    $part['inner_hits'][$field_name] = $values;
                }
            }

            // sanitize result
            $response['data'][] = $part;
        }

        unset($data);

        return $response;
    }


    protected function createHighlight(array $filters): array
    {
        $highlights = [
            'number_of_fragments' => 0,
            'pre_tags' => ['<mark>'],
            'post_tags' => ['</mark>'],
            'fields' => [],
        ];

        $filterConfig = $this->getSearchConfig();

        foreach ($filters as $filterName => $filterValue) {
            $filterType = $filterConfig[$filterName]['type'] ?? false;
            switch ($filterType) {
                case self::FILTER_TEXT:
                case self::FILTER_QUERYSTRING:
                    $field = $filterValue['field'] ?? $filterName;
                    $highlights['fields'][$filterName] = new \stdClass();
                    break;
            }
        }

        return $highlights;
    }

    private static function formatHighlight(string $highlight): array
    {
        $lines = explode(PHP_EOL, html_entity_decode($highlight));
        $result = [];
        foreach ($lines as $number => $line) {
            // Remove \r
            $line = trim($line);
            // Each word is marked separately, so we only need the lines with <mark> in them
            if (strpos($line, '<mark>') !== false) {
                $result[$number] = $line;
            }
        }
        return $result;
    }

    protected function sanitizeSearchResult(array $result)
    {
        return $result;
    }

    protected function addAggregations(Query|Aggregation\AbstractAggregation $aggParentQuery, array $aggConfigs, array $arrFilterValues): void
    {
        foreach ($aggConfigs as $aggName => $aggConfig) {
            $this->addAggregation($aggParentQuery, $aggConfig, $arrFilterValues);
        }
    }

    protected function addAggregation(Query|Aggregation\AbstractAggregation $aggParentQuery, array $aggConfig, array $arrFilterValues): void
    {
        $aggName = $aggConfig['name'];
        $aggType = $aggConfig['type'];
        $aggField = $aggConfig['field'];
        $countTopDocuments = $aggConfig['countTopDocuments'];
        $aggLimit = $aggConfig['limit'] ?? self::MAX_AGG;
        $aggIsNested = AggregationConfig::isNestedAggregation($aggConfig);

        // skip aggregations with false condition
        if ( is_callable($aggConfig['condition'] ?? null) ) {
            if ( !$aggConfig['condition']($aggConfig, $arrFilterValues) ) { // signature changed!
                return;
            }
        }

        switch ($aggType) {
            case self::AGG_GLOBAL_STATS:
                $aggParentQuery->addAggregation(
                    (new Aggregation\Stats($aggName))
                        ->setField($aggField)
                );
                break;
            case self::AGG_CARDINALITY:
                $aggParentQuery->addAggregation(
                    (new Aggregation\Cardinality($aggName))
                        ->setField($aggField)
                );
                break;
            case self::AGG_KEYWORD:
                $aggField = $aggField . '.keyword';
            case self::AGG_TERMS:
                $aggFilterValues = $arrFilterValues[$aggName]['value'] ?? [];

                $aggTerm = (new Aggregation\Terms($aggName))
                    ->setSize($aggLimit)
                    ->setField($aggField);

                // allow 0 doc count if aggregation is filtered
                if ( count($aggFilterValues) ) {
                    $aggTerm->setMinimumDocumentCount(0);
                }

                // count top documents?
                $aggIsNested && $countTopDocuments && $aggTerm->addAggregation(new Aggregation\ReverseNested('top_reverse_nested'));

                // subaggregations
                $this->addAggregations($aggTerm, $aggConfig['aggregations'], $arrFilterValues);
//                foreach($aggConfig['aggregations'] as $subAggName => $subAggConfig) {
//                    $this->addAggregation($aggTerm, $subAggConfig, $arrFilterValues);
//                }

                $aggParentQuery->addAggregation($aggTerm);
                break;
            case self::AGG_BOOLEAN:
            case self::AGG_NUMERIC:
                $aggTerm = (new Aggregation\Terms($aggName))
                    ->setSize($aggLimit)
                    ->setField($aggField);

                // count top documents?
                $aggIsNested && $countTopDocuments && $aggTerm->addAggregation(new Aggregation\ReverseNested('top_reverse_nested'));

                // subaggregations
                $this->addAggregations($aggTerm, $aggConfig['aggregations'], $arrFilterValues);
//                foreach($aggConfig['aggregations'] as $subAggName => $subAggConfig) {
//                    $this->addAggregation($aggTerm, $subAggConfig, $arrFilterValues);
//                }

                $aggParentQuery->addAggregation($aggTerm);
                break;
            case self::AGG_OBJECT_ID_NAME:
                // todo: remove 'locale' option, add 'keywordField' that overrides default '.id_name.keyword'
                $aggLocalePrefix = ($aggConfig['locale'] ?? null) ? '.'.$aggConfig['locale'] : '';
                $aggField = $aggField . '.id_name'.$aggLocalePrefix.'.keyword';
                $aggFilterValues = $arrFilterValues[$aggName]['value'] ?? [];

                $aggTerm = (new Aggregation\Terms($aggName))
                    ->setSize($aggLimit)
                    ->setField($aggField);

                // count top documents?
                $aggIsNested && $countTopDocuments && $aggTerm->addAggregation(new Aggregation\ReverseNested('top_reverse_nested'));

                // allow 0 doc count if aggregation is filtered
                if ( count($aggFilterValues) ) {
                    $aggTerm->setMinimumDocumentCount(0);
                }

                // subaggregations
                $this->addAggregations($aggTerm, $aggConfig['aggregations'], $arrFilterValues);
//                foreach($aggConfig['aggregations'] as $subAggName => $subAggConfig) {
//                    $this->addAggregation($aggTerm, $subAggConfig, $arrFilterValues);
//                }

                $aggParentQuery->addAggregation($aggTerm);

                // count missing
                if ($config['countMissing'] ?? false) {
                    $aggCountMissing = new Aggregation\Missing('count_missing', $aggField);
                    $aggIsNested && $countTopDocuments && $aggCountMissing->addAggregation(new Aggregation\ReverseNested('top_reverse_nested'));
                    $aggParentQuery->addAggregation($aggCountMissing);
                }
                // count any
                if ($config['countAny'] ?? false) {
                    $aggCountAny = new Aggregation\Filters('count_any');
                    $aggIsNested && $countTopDocuments && $aggCountAny->addAggregation(new Aggregation\ReverseNested('top_reverse_nested'));
                    $aggCountAny->addFilter(new Query\Exists($aggField));
                    $aggParentQuery->addAggregation($aggCountAny);
                }
                break;
            case self::AGG_REVERSE_NESTED:
                $aggReverseNested = new Aggregation\ReverseNested($aggName);

                $this->addAggregations($aggReverseNested, $aggConfig['aggregations'], $arrFilterValues);
//                foreach($aggConfig['aggregations'] as $subAggName => $subAggConfig) {
//                    $this->addAggregation($aggReverseNested, $subAggConfig, $arrFilterValues);
//                }

                $aggParentQuery->addAggregation($aggReverseNested);
                break;
        }
    }

    protected function parseAggregationResult(array $arrAggData, array $aggConfig, array $arrFilterValues = []): mixed
    {
        $aggName = $aggConfig['name'];
        $aggType = $aggConfig['type'];
        $aggFilterValues = $arrFilterValues[$aggName]['value'] ?? [];

        // get aggregation results
        $aggData = $arrAggData['global_aggregation'][$aggName] ?? $arrAggData[$aggName] ?? [];

        $results = [];

        switch ($aggType) {
            case self::AGG_GLOBAL_STATS:
            case self::AGG_CARDINALITY:
                $aggResults = $this->getAggregationData($aggData, $aggName, $aggName);
                $results = $aggResults;
                break;
            case self::AGG_BOOLEAN:
                $aggResults = $this->getAggregationData($aggData, $aggName, $aggName);
                $items = [];
                foreach ($aggResults['buckets'] ?? [] as $result) {
                    if (!isset($result['key'])) continue;
                    $items[] = [
                        'id' => $result['key'],
                        'name' => $result['key_as_string'],
                        'count' => (int) ($result['top_reverse_nested']['doc_count'] ?? $result['doc_count'])
                    ];
                }
                $results = $this->sanitizeTermAggregationItems($items, $aggConfig, $aggFilterValues);
                break;
            case self::AGG_NUMERIC:
            case self::AGG_KEYWORD:
            case self::AGG_TERMS:
                $aggResults = $this->getAggregationData($aggData, $aggName, $aggName);
                $aggFilterValuesFlipped = array_flip($aggFilterValues);
                $aggFormatter = $aggConfig['formatter'] ?? "facet";

                $items = [];

                switch($aggFormatter)
                {
                    case 'key':
                        $results = $aggResults['buckets'][0]['key'] ?? null;
                        break;
                    case 'keys':
                        foreach ($aggResults['buckets'] ?? [] as $bucket) {
                            if (!isset($bucket['key'])) continue;
                            $items[] = $bucket['key'];
                        }
                        $results = $items;

                        break;
                    case 'facet':
                    default:
                        foreach ($aggResults['buckets'] ?? [] as $bucket) {
                            if (!isset($bucket['key'])) continue;

                            // output facet
                            $item = [
                                'id' => $bucket['key'],
                                'name' => $bucket['key'],
                                'count' => (int) ($bucket['top_reverse_nested']['doc_count'] ?? $bucket['doc_count'])
                            ];
                            if ( isset($aggFilterValuesFlipped[$bucket['key']]) ) {
                                $item['active'] = true;
                            }

                            // subaggregations
                            foreach($aggConfig['aggregations'] as $subAggName => $subAggConfig) {
                                $item[$subAggName] = $this->parseAggregationResult($bucket, $subAggConfig, $arrFilterValues);
                            }

                            $items[] = $item;
                        }
                        $results = $this->sanitizeTermAggregationItems($items, $aggConfig, $aggFilterValues);
                        break;
                }

                break;
            case self::AGG_OBJECT_ID_NAME:
                $items = [];
                $aggFilterValuesFlipped = array_flip($aggFilterValues);

                // get none count
                if ($aggConfig['countMissing'] ?? false) {
                    $aggResults = $this->getAggregationData($aggData, $aggName, 'count_missing');
                    if ( $aggResults['doc_count'] ?? null) {
                        $item = [
                            'id' => $aggConfig['noneKey'],
                            'name' => $aggConfig['noneLabel'],
                            'count' => (int) ($aggResults['top_reverse_nested']['doc_count'] ?? $aggResults['doc_count'])
                        ];
                        if ( isset($aggFilterValuesFlipped[(int) $aggConfig['noneKey']]) ) {
                            $item['active'] = true;
                        }
                        $items[] = $item;
                    }
                }

                // get any count
                if ($aggConfig['countAny'] ?? false) {
                    $aggResults = $this->getAggregationData($aggData, $aggName, 'count_any');
                    if ( $aggResults['buckets'][0]['doc_count'] ?? null) {
                        $item = [
                            'id' => $aggConfig['anyKey'],
                            'name' => $aggConfig['anyLabel'],
                            'count' => (int) ($aggResults['buckets'][0]['top_reverse_nested']['doc_count'] ?? $aggResults['buckets'][0]['doc_count'])
                        ];
                        if ( isset($aggFilterValuesFlipped[(int) $aggConfig['anyKey']]) ) {
                            $item['active'] = true;
                        }
                        $items[] = $item;
                    }
                }

                // get values
                $aggResults = $this->getAggregationData($aggData, $aggName, $aggName);
                foreach ($aggResults['buckets'] ?? [] as $bucket) {
                    if (!isset($bucket['key'])) continue;
                    $parts = explode('_', $bucket['key'], 2);
                    // create item
                    $item = [
                        'id' => $parts[0],
                        'name' => $parts[1] ?? $parts[0],
                        'count' => (int) ($bucket['top_reverse_nested']['doc_count'] ?? $bucket['doc_count'])
                    ];
                    // item active?
                    if ( isset($aggFilterValuesFlipped[$parts[0]]) ) {
                        $item['active'] = true;
                    }
                    // collect sub aggregations
                    foreach($aggConfig['aggregations'] as $subAggName => $subAggConfig) {
                        $item[$subAggName] = $this->parseAggregationResult($bucket, $subAggConfig, $arrFilterValues);
                    }

                    $items[] = $item;
                }
                $results = $this->sanitizeTermAggregationItems($items, $aggConfig, $aggFilterValues);
                break;
            case self::AGG_REVERSE_NESTED:
                foreach($aggConfig['aggregations'] as $subAggName => $subAggConfig) {
                    $results[$subAggName] = $this->parseAggregationResult($aggData, $subAggConfig, $arrFilterValues);
                }
                break;
        }

        return $results;
    }

    /**
     * @param array $arrFilterValues
     * @return array
     *
     * There are 3 types of filters in an aggregation query
     * - filters that reduce the document set for all aggregations.
     * - filters that reduce the document set for each aggregation separately.
     *   ex: aggregations that allow multiselect must not be filtered by their own selected values
     * - filters that reduce the nested document set.
     *   ex: think of a collection of objects with properties 'type' and 'subtype'. an aggregation on 'subtype'
     *   must also filter the nested set of objects based on the value of 'type'.
     *
     */

    protected function _aggregate(array $arrFilterValues, ?array $limitConfigKeys = null, ?array $excludeConfigKeys = null): array
    {
        // get aggregation configurations
        $arrAggregationConfigs = $this->getAggregationConfig();
        if (!count($arrAggregationConfigs)) {
            return [];
        }

        // limit aggregation configs?
        if ( $limitConfigKeys ) {
            $arrAggregationConfigs = array_intersect_key($arrAggregationConfigs, array_flip($limitConfigKeys));
        }

        // exclude aggregation configs?
        if ( $excludeConfigKeys ) {
            $arrAggregationConfigs = array_diff_key($arrAggregationConfigs, array_flip($excludeConfigKeys));
        }

        $arrFilterConfigs = $this->getSearchConfig();

        // event onInitAggregationConfig
        $this->onInitAggregationConfig($arrAggregationConfigs, $arrFilterValues);

        // get filters used in multiselect aggregations
        // these filters are added to each aggregation and don't filter the global set
        $arrAggregationFilterConfigs = $this->getAggregationFilters();

        // create global set search query
        // exclude filters used in multiselect aggregations, will be added as aggregation filters
        $arrGlobalSetFilterConfigs = array_diff_key($arrFilterConfigs, $arrAggregationFilterConfigs);
        $query = (new Query())
            ->setQuery($this->createSearchQuery($arrFilterValues, $arrGlobalSetFilterConfigs))
            ->setSize(0); // Only aggregation will be used

        // create global aggregation (unfiltered, full dataset)
        // global aggregations will be added as sub-aggregations to this aggregation
        $aggGlobalQuery = new Aggregation\GlobalAggregation("global_aggregation");
        $query->addAggregation($aggGlobalQuery);

        // walk aggregation configs
        foreach ($arrAggregationConfigs as $aggName => $aggConfig) {
            $aggField = $aggConfig['field'];
            $aggIsGlobal = AggregationConfig::isGlobalAggregation($aggConfig); // global aggregation?

            // skip inactive aggregations
            if (!$aggConfig['active']) {
                continue;
            }

            // skip aggregations with false condition
            if ( is_callable($aggConfig['condition'] ?? null) ) {
                if ( !$aggConfig['condition']($aggConfig, $arrFilterValues) ) {
                    continue;
                }
            }

            // set aggregation parent
            $aggParentQuery = $aggIsGlobal ? $aggGlobalQuery : $query;

            // always add aggregation filter (if not global)
            // - easier to parse results
            // + remove excludeFilter
            // + don't filter myself
            if (!$aggIsGlobal) {
                // calculate filters used in aggregation
                $aggFilterConfigs = array_diff_key($arrAggregationFilterConfigs, array_flip($aggConfig['excludeFilter'] ?? []));
                unset($aggFilterConfigs[$aggName]);

                // calculate filter values used in aggregation
                // instead of excluding filters, more correct to remove filter values for that filter, default value possible, no?
                $aggFilterValues = array_diff_key($arrFilterValues, array_flip($aggConfig['excludeFilter'] ?? []));
                unset($aggFilterValues[$aggName]);

                // fixed!
                $filterQuery = $this->createSearchQuery($aggFilterValues, $aggFilterConfigs);

                $aggSubQuery = new Aggregation\Filter($aggName);
                $aggSubQuery->setFilter($filterQuery);

                $aggParentQuery->addAggregation($aggSubQuery);
                $aggParentQuery = $aggSubQuery;
            }

            // nested aggregation? filter nested set!
            $aggIsNested = AggregationConfig::isNestedAggregation($aggConfig);
            if ($aggIsNested) {
                // add nested path to filed
                $aggNestedPath = $aggConfig['nestedPath'];

                // add nested aggregation
                $aggSubQuery = new Aggregation\Nested($aggName, $aggNestedPath);
                $aggParentQuery->addAggregation($aggSubQuery);
                $aggParentQuery = $aggSubQuery;

                // reduce nested set based on filters
                // precedence:
                // - aggregation has $aggConfig['filters'], use that list
                // - if not, apply all aggregation filters for the current nested path
                // - reduce the set filters based on $aggConfig['excludeFilters']

                // aggregation has filter config?
                // todo: instead of manual filter list, can this be done by getting all filters with the same nested path?
                // all filters with the same nested path? not always needed,
                $aggNestedFilterConfigs = $aggConfig['filters'];
                if ( !is_array($aggNestedFilterConfigs) ) {
                    $aggNestedFilterConfigs = [];
                    foreach( $arrAggregationFilterConfigs as $config ) {
                        if ( ($config['nestedPath'] ?? null) === $aggNestedPath ) {
                            $aggNestedFilterConfigs = array_merge($aggNestedFilterConfigs, $config['filters'] ?? []);
                        }
                    }
                }
//                $this->debug && dump($aggNestedFilterConfigs);

                // calculate filter values used in aggregation
                // instead of excluding filters, more correct to remove filter values for that filter, default value possible, no?
                // 1: expand excludeFilter with child filters
                // 2: only keep filter values needed in filter configs
                // 3: remove excluded filter keys
                // 4: don't filter myself
                $arrExcludeFilterKeys = [];
                foreach ($aggConfig['excludeFilter'] ?? [] as $filterKey) {
                    if ( $arrFilterConfigs[$filterKey]['filters'] ?? []) {
                        $arrExcludeFilterKeys = array_merge($arrExcludeFilterKeys, array_keys($arrFilterConfigs[$filterKey]['filters']) );
                    }
                }
                $aggFilterValues = $arrFilterValues;
                // todo 25/02/21: are the 2 lines below needed? we limit the number of filters,
                // so we don't need to limit the filter values, only unset our own filter value
//                $aggFilterValues = array_intersect_key($arrFilterValues, $aggNestedFilterConfigs);
//                $aggFilterValues = array_diff_key($aggFilterValues, array_flip($arrExcludeFilterKeys));
                unset($aggFilterValues[$aggName]);
//                $this->debug && dump($aggFilterValues);

                // create query to reduce set of nested documents
                $filterQuery = $this->createSearchQuery($aggFilterValues, $aggNestedFilterConfigs);

                // aggregation has a limit on allowed values?
                if ($aggConfig['allowedValue'] ?? false) {
                    $allowedValue = is_array($aggConfig['allowedValue']) ? $aggConfig['allowedValue'] : [ $aggConfig['allowedValue'] ];
                    $filterQuery->addFilter(
                        new Query\Terms($aggField, $allowedValue)
                    );
                }

                // add filter query to aggregation (if not empty)
                if ($filterQuery->count()) {
                    $aggSubQuery = new Aggregation\Filter($aggName);
                    $aggSubQuery->setFilter($filterQuery);

                    $aggParentQuery->addAggregation($aggSubQuery);
                    $aggParentQuery = $aggSubQuery;
                }
            }

            // add aggregation
            $this->addAggregation($aggParentQuery, $aggConfig, $arrFilterValues);
        }

        $this->debug && dump(json_encode($query->toArray(),JSON_PRETTY_PRINT));

        // parse query result
        $searchResult = $this->getIndex()->search($query);
        $results = [];

        // parse aggregation results
        $arrAggData = $searchResult->getAggregations();
        $this->debug && dump($arrAggData);

        foreach ($arrAggregationConfigs as $aggName => $aggConfig) {
            $results[$aggName] = $this->parseAggregationResult($arrAggData, $aggConfig, $arrFilterValues);
        }

        return $results;
    }

    private function getAggregationData(array $data, string $top_agg_name, string $agg_name): ?array
    {
        $results = $data;
        while($results[$agg_name] ?? $results[$top_agg_name] ?? null) {
            $results = $results[$agg_name] ?? $results[$top_agg_name];
        }
        return $results;
    }

    /**
     * Return filters that are used in aggregations
     * Todo: Now based on aggregation type or config (mostly nested), should be based on aggregation config check!
     */
    private function getAggregationFilters(): array
    {

        $filters = $this->getSearchConfig();
        $aggOrFilters = [];
        foreach ($filters as $filterName => $filterConfig) {
            $filterType = $filterConfig['type'];
            switch ($filterType) {
                case self::FILTER_NESTED_ID:
                case self::FILTER_OBJECT_ID:
                case self::FILTER_NESTED_MULTIPLE:
                    if (($filterConfig['aggregationFilter'] ?? true)) {
                        $aggOrFilters[$filterName] = $filterConfig;
                    }
                    break;
                default:
                    if (($filterConfig['aggregationFilter'] ?? false)) {
                        $aggOrFilters[$filterName] = $filterConfig;
                    }
                    break;
            }
        }

        return $aggOrFilters;
    }

    protected function sortAggregationResult(?array &$agg_result, array $aggConfig): void
    {
        if (!$agg_result) {
            return;
        }
        usort($agg_result, function ($a, $b) use ($aggConfig) {

            // Place 'any', 'none' filters above
            if(($a['name'] === 'none' || $a['name'] === 'any') && ($b['name'] !== 'any' && $b['name'] !== 'none')) {
                return -1;
            }
            if(($a['name'] !== 'any' && $a['name'] !== 'none') && ($b['name'] === 'any' || $b['name'] === 'none')) {
                return 1;
            }

            // Place true before false
            if ($a['name'] === 'false' && $b['name'] === 'true') {
                return 1;
            }
            if ($a['name'] === 'true' && $b['name'] === 'false') {
                return -1;
            }

            return strnatcasecmp($a['name'], $b['name']);
        });
    }

}
