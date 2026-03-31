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

        // Process the config to build proper Elastica configuration
        $elasticaConfig = $this->buildElasticaConfig($config);

        parent::__construct($elasticaConfig);
    }

    public function getIndex(string $name): Index
    {
        return parent::getIndex(($this->indexPrefix ? $this->indexPrefix .'_' : '').$name );
    }

    /**
     * Build Elastica configuration from application config
     *
     * @param array $config
     * @return array
     */
    private function buildElasticaConfig(array $config): array
    {
        // construct url
        if ($config['url'] ?? null) {
            $url_parts = parse_url($config['url']);
            // fix missing port
            $url_parts['port'] = $url_parts['port'] ?? ($url_parts['scheme'] === 'https' ? 443 : 9000);

            // create url based on url_parts
            $url = $url_parts['scheme'] . '://' . $url_parts['host'];
            if (isset($url_parts['port'])) {
                $url .= ':' . $url_parts['port'];
            }
            if (isset($url_parts['path'])) {
                $url .= rtrim($url_parts['path'], '/') . '/';
            } else {
                $url .= '/';
            }

            $transport = $url_parts['scheme'] ?? 'http';
        } else {
            $host = $config['host'] ?? 'localhost';
            $port = $config['port'] ?? 9200;
            $transport = $config['transport'] ?? "http";

            // Build the base URL
            $url = sprintf('%s://%s:%d/', $transport, $host, $port);
        }

        // authentication details
        $username = $config['username'] ?? $config['user'] ?? null;
        $password = $config['password'] ?? null;

        // SSL verification setting
        $sslVerification = $config['ssl_verification'] ?? true;

        // Build Elastica config array
        $elasticaConfig = [
            'url' => $url,
        ];

        // Add authentication if username & password is provided
        if (!empty($username) && !empty($password)) {
            $elasticaConfig['username'] = $username;
            $elasticaConfig['password'] = $password;
            $elasticaConfig['auth_type'] = 'basic';
        }

        // Configure SSL verification
        if ($transport === 'https') {
            if (!$sslVerification) {
                // Disable SSL verification (not recommended for production)
                $elasticaConfig['curl'] = [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                ];
            } else {
                // Enable SSL verification (default, recommended)
                $elasticaConfig['curl'] = [
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                ];
            }
        }

        return $elasticaConfig;
    }

}