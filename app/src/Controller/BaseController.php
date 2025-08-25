<?php
namespace App\Controller;

use App\Service\ElasticSearch\Base\SearchServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BaseController extends AbstractController
{
    /**
     * The folder where relevant templates are located.
     */
    protected string $templateFolder;
    protected SearchServiceInterface $searchService;

    /**
     * Return shared urls
     * @return array
     */
    protected function getSharedAppUrls(): array {
        // urls
        $urls = [
            // text
            'text_search' => $this->generateUrl('text_search'),
            'text_search_api' => $this->generateUrl('text_search_api'),
            'text_paginate' => $this->generateUrl('text_paginate'),
            'text_get_single' => $this->generateUrl('text_get_single', ['id' => 'text_id']),
        ];

        return $urls;
    }


    protected function _paginate(Request $request): Response {
        // search
        $data = $this->searchService->searchRAW(
            $request->query->all(),
            ['id']
        );

        // return array of id's
        $result = [];
        foreach($data['data'] as $item) {
            $result[] = $item['id'];
        }

        return new JsonResponse($result);
    }

    protected function _searchAPI(Request $request, SearchMode $mode = SearchMode::SEARCH_AGGREGATE, ?array $useAggregationKeys = null, ?array $excludeAggregationKeys = null, callable|array|null $callback = null): Response {
        try {
            switch ($mode) {
                case SearchMode::SEARCH_AGGREGATE:
                    $data = $this->searchService->searchAndAggregate(
                        $this->sanitizeSearchRequest($request->query->all()),
                        $useAggregationKeys,
                        $excludeAggregationKeys
                    );
                    break;
                case SearchMode::SEARCH:
                    $data = $this->searchService->search(
                        $this->sanitizeSearchRequest($request->query->all())
                    );
                    break;
                case SearchMode::AGGREGATE:
                    $data = $this->searchService->aggregate(
                        $this->sanitizeSearchRequest($request->query->all()['filters'] ?? []),
                        $useAggregationKeys,
                        $excludeAggregationKeys
                    );
                    break;
            }
            // apply callback
            if ($callback && is_callable($callback)) {
                $data = $callback($data);
            }
            // debug?
            if ($request->query->getBoolean('debug', false)) {
                return $this->render('pages/default/index.html.twig', [
                    'page_title' => 'debug',
                    'controller_name' => 'TestController',
                ]);
            }
            // return json response
            return new JsonResponse($data);
        } catch (\Throwable $e) {
            // debug?
            if ($request->query->getBoolean('debug', false)) {
                dump($e);
                return $this->render('pages/default/index.html.twig', [
                    'page_title' => 'debug',
                    'controller_name' => 'TestController',
                ]);
            }
            // return json error response
            return new JsonResponse(['error' => $e->getMessage(), 'type' => get_class($e) ], 400);
        }
    }

    protected function _search(Request $request, array $props = [], array $extraRoutes = []): Response {
        // urls
        $urls = $this->getSharedAppUrls();
        foreach( $extraRoutes as $key => $val ) {
            $urls[$key] = $urls[$val] ?? $val;
        }

        // html response
        return $this->render(
            $this->templateFolder. '/search.html.twig',
            [
                'urls' => json_encode($urls),
            ] + $props
        );
    }

    /**
     * Sanitize data from request string
     * @param array $params
     * @return array
     */
    protected function sanitizeSearchRequest(array $params): array
    {
        return $params;
    }

}