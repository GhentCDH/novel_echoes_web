<?php
namespace App\Service\ElasticSearch\Base;

class SearchConfig implements SearchConfigInterface
{
    private array $searchConfig;

    public function setConfig(array $config): void
    {
        $this->searchConfig = $config;

        foreach ($this->searchConfig as $filterName => $filterConfig) {
            $this->searchConfig[$filterName] = $this->sanitizeConfig($filterName, $filterConfig);
        }
    }

    public function getConfig(): array
    {
        return $this->searchConfig;
    }

    public static function sanitizeConfig(string $name, array $config, string $prefix = null): array
    {
        $arrFieldPrefix = [];
        if ( $prefix ) {
            $arrFieldPrefix[] = $prefix;
        }

        $config['name'] = $name;
        $config['type'] = $config['type'] ?? self::DEFAULT_FILTER_TYPE;
        if ( $config['type'] !== self::FILTER_NESTED_MULTIPLE ) {
            $config['field'] = $config['field'] ?? $config['name'];
        }
        if(static::isNestedFilter($config)) {
            $config['nestedPath'] = $config['nestedPath'] ?? $config['field'];
            $arrFieldPrefix[] = $config['nestedPath'];
        }
        $config['anyKey'] = $config['anyKey'] ?? self::ANY_KEY;
        $config['noneKey'] = $config['noneKey'] ?? self::NONE_KEY;

        // subfilters?
        if($config['filters'] ?? []) {
            foreach($config['filters'] as $sub_name => $sub_config) {
                $config['filters'][$sub_name] = static::sanitizeConfig($sub_name, $sub_config, $config['nestedPath'] ?? null);
            }
        }

        // fix lazy configuration
        if ( count($arrFieldPrefix) ) {
            $fieldPrefix = implode('.', $arrFieldPrefix).'.';
            // add missing field prefix?
            if ( isset($config['field']) && $config['field'] && !(str_starts_with($config['field'], $fieldPrefix) || $fieldPrefix === $config['field'].'.') ) {
                $config['field'] = $fieldPrefix.$config['field'];
            }
        }

        return $config;
    }

    public static function isNestedFilter($config)
    {
        return (in_array($config['type'], [self::FILTER_NESTED_ID, self::FILTER_NESTED_MULTIPLE], true) || ($config['nestedPath'] ?? false));
    }


}