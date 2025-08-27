<?php

namespace App\Controller;

use App\Repository\TextRepository;
use App\Resource\ElasticSearch\ElasticTextResource;
use App\Service\ElasticSearch\Base\IndexServiceInterface;
use App\Service\ElasticSearch\Index\TextIndexService;
use App\Service\ElasticSearch\Search\TextSearchService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class TextController extends BaseController
{
    protected string $templateFolder = 'pages/text';

    public function __construct(TextSearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * @Route("/text", name="text_search", methods={"GET"})
     */
    public function texts(Request $request): Response
    {
        $urls = $this->getSharedAppUrls();
        return $this->render(
            $this->templateFolder . '/search.html.twig',
            [
                'title' => 'Search texts',
                'urls' => json_encode(array_merge($urls, [
                    'search_api' => $urls['text_search_api'],
                    'paginate' => $urls['text_paginate'],
                ])),
            ]
        );
    }

    /**
     * @Route("/text/{id}", name="text_get_single", priority=-10, methods={"GET"})
     */
    public function text(string $id, Request $request): Response
    {
        if (in_array('application/json', $request->getAcceptableContentTypes())) {
            try {
                $data = $this->searchService->getSingle($id);
            } catch (NotFoundHttpException $e) {
                return new JsonResponse(
                    ['error' => ['code' => Response::HTTP_NOT_FOUND, 'message' => $e->getMessage()]],
                    Response::HTTP_NOT_FOUND
                );
            }
            return new JsonResponse($data);
        } else {
            $data = $this->searchService->getSingle($id);
            return $this->render(
                $this->templateFolder . '/detail.html.twig',
                [
                    'title' => $data['title'],
                    'urls' => json_encode($this->getSharedAppUrls()),
                ]
            );
        }
    }

    /**
     * @Route("/text/search", name="text_search_api", methods={"GET"})
     */
    public function search(Request $request): Response
    {
        $mode = SearchMode::fromValue($request->query->get('mode', null)) ?: SearchMode::SEARCH_AGGREGATE;

        return $this->_searchAPI($request, $mode);
    }

    /**
     * @Route("/text/paginate", name="text_paginate", methods={"GET"})
     */
    public function paginate(Request $request): Response
    {
        return $this->_paginate($request);
    }
}
