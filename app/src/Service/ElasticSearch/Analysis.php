<?php

namespace App\Service\ElasticSearch;

/**
 */
class Analysis
{
    /**
     * Elasticsearch config for Special Analysis
     * @var array
     */
    const ANALYSIS = [
        'filter' => [
            'greek_stemmer' => [
                'type' => 'stemmer',
                'language' => 'greek',
            ],
        ],
        'char_filter' => [
            # Add 10 leading zeros
            'add_leading_zeros' => [
                'type' => 'pattern_replace',
                'pattern' => '(\\d+)',
                'replacement' => '0000000000$1',
            ],
            # Remove leading zeros so the total number of digits is 10
            'remove_leading_zeros' => [
                'type' => 'pattern_replace',
                'pattern' => '(0+)(?=\\d{10})',
                'replacement' => '',
            ],
            'remove_par_brackets_filter' => [
                'type' => 'mapping',
                'mappings' => [
                    '( =>',
                    ') =>',
                    '[ =>',
                    '] =>',
                    '< =>',
                    '> =>',
                    '| =>',
                    '+ =>',
                ],
            ],
            'remove_quotes_filter' => [
                'type' => 'mapping',
                'mappings' => [
                    '" =>',
                    '“ =>',
                    '” =>',
                    '« =>',
                    '» =>',
                    '\' =>',
                ],
            ],
        ],
        'analyzer' => [
            'custom_greek_stemmer' => [
                'tokenizer' => 'icu_tokenizer',
                'char_filter' => [
                    'remove_par_brackets_filter'
                ],
                'filter' => [
                    'icu_folding',
                    'lowercase',
                    'greek_stemmer',
                ],
            ],
            'custom_greek_original' => [
                'tokenizer' => 'icu_tokenizer',
                'char_filter' => [
                    'remove_par_brackets_filter'
                ],
                'filter' => [
                    'icu_folding',
                    'lowercase',
                ],
            ],
        ],
        'normalizer' => [
            'case_insensitive' => [
                'filter' => [
                    'lowercase',
                ],
            ],
            'custom_greek' => [
                'char_filter' => [
                    'remove_quotes_filter'
                ],
                'filter' => [
                    'icu_folding',
                    'lowercase',
                ],
            ],
            'text_digits' => [
                'char_filter' => [
                    'add_leading_zeros',
                    'remove_leading_zeros',
                ],
            ],
        ],
    ];
}
