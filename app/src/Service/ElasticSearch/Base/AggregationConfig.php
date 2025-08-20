<?php
namespace App\Service\ElasticSearch\Base;

class AggregationConfig implements SearchConfigInterface
{
    private array $aggregation;

    public function setConfig(array $config): void
    {
        $this->aggregation = $config;

        foreach ($this->aggregation as $filterName => $filterConfig) {
            $this->aggregation[$filterName] = static::sanitizeConfig($filterName, $filterConfig);
        }
    }

    public function getConfig(): array
    {
        return $this->aggregation;
    }

    private static function sanitizeConfig(string $name, array $config, string $prefix = null): array
    {
        $arrFieldPrefix = [];
        if ( $prefix ) {
            $arrFieldPrefix[] = $prefix;
        }

        $config['name'] = $name;
        $config['field'] = $config['field'] ?? $config['name'];
        $config['active'] = (bool) ($config['active'] ?? true);
        $config['limit'] = isset($config['limit']) ? intval($config['limit']) : null;
        $config['safeLimit'] = isset($config['safeLimit']) ? intval($config['safeLimit']) : null;
        if(static::isNestedAggregation($config)) {
            $config['nestedPath'] = $config['nestedPath'] ?? $config['field'];
            $arrFieldPrefix[] = $config['nestedPath'];
        }
        $config['countTopDocuments'] = (bool) ($config['countTopDocuments'] ?? True);

        $config['countMissing'] = (bool) ($config['countMissing'] ?? false);
        $config['countAny'] = (bool) ($config['countAny'] ?? false);
        $config['anyKey'] = $config['anyKey'] ?? self::ANY_KEY;
        $config['anyLabel'] = $config['anyLabel'] ?? self::ANY_LABEL;
        $config['noneKey'] = $config['noneKey'] ?? self::NONE_KEY;
        $config['noneLabel'] = $config['noneLabel'] ?? self::NONE_LABEL;

        // sanitize filters
        $config['filters'] = $config['filters'] ?? null; // empty filter list has meaning: don't filter!
        if(is_array($config['filters'])) {
            foreach($config['filters'] as $sub_name => $sub_config) {
                $config['filters'][$sub_name] = SearchConfig::sanitizeConfig($sub_name, $sub_config, $config['nestedPath'] ?? null);
            }
        }

        // sanitize sub aggregations
        $config['aggregations'] = $config['aggregations'] ?? [];
        if($config['aggregations']) {
            foreach($config['aggregations'] as $sub_name => $sub_config) {
                $config['aggregations'][$sub_name] = static::sanitizeConfig($sub_name, $sub_config, $config['nestedPath'] ?? null);
            }
        }

        // fix lazy configuration
        if ( count($arrFieldPrefix) ) {
            $fieldPrefixWithDot = implode('.', $arrFieldPrefix).'.';
            $fieldPrefix = implode('.', $arrFieldPrefix);
            // add missing field prefix?
            if ( isset($config['field']) && !($config['field'] == $fieldPrefix || str_starts_with($config['field'], $fieldPrefixWithDot)) ) {
                $config['field'] = $fieldPrefix.$config['field'];
            }
        }

        return $config;
    }

    public function enable(string $name): void
    {
        $this->aggregation[$name]['active'] = true;
    }

    public function disable(string $name): void
    {
        $this->aggregation[$name]['active'] = false;
    }

    public function enableAll(): void
    {
        foreach ($this->aggregation as $name => $config) {
            $this->enable($name);
        }
    }

    public function disableAll(): void
    {
        foreach ($this->aggregation as $name => $config) {
            $this->disable($name);
        }
    }

    public static function isNestedAggregation($config): bool
    {
//        return (in_array($config['type'], [], true) || ($config['nestedPath'] ?? false));
        return $config['nestedPath'] ?? false;
    }

    /** check if aggregation works on global dataset or filtered dataset */
    public static function isGlobalAggregation($config): bool
    {
        return (in_array($config['type'], [self::AGG_GLOBAL_STATS], true) || ($config['globalAggregation'] ?? false));
    }

}