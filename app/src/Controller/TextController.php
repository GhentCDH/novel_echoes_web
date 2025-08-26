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

    /**
     * @Route("/webhook/text/reindex", name="webhook_text", methods={"POST"})
     *
     */
    public function reindexTextWebhook(Request $request, TextRepository $repository, IndexServiceInterface $indexService): JsonResponse
    {
        // validate webhook key
        $webhookKey = $request->headers->get('novelechoes-webhook-key');
        if (!$webhookKey) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $expectedKey =  $this->getParameter('reindexTextWebhook.apiKey') ?? null;
        if (!$expectedKey || $webhookKey !== $expectedKey) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        // validate payload
        $data = json_decode($request->getContent(), true);
        if (!isset($data['textId'], $data['state'])) {
            return new JsonResponse(['error' => 'Missing textId or state'], Response::HTTP_BAD_REQUEST);
        }
        $textState = in_array($data['state'] ?? "unknown", ["create", "update", "delete"], TRUE) ? $data['state'] : null;
        $textId = intval($data['textId']);
        if (!$textState || !$textId) {
            return new JsonResponse(['error' => 'Invalid textId or state'], Response::HTTP_BAD_REQUEST);
        }

        // process request
        try {
            $text = $repository->find($textId);

            switch($textState) {
                case 'delete':
                    if ($text) {
                        return new JsonResponse(['error' => "Text with id {$textId} exists in database"], Response::HTTP_NOT_FOUND);
                    }
                    $indexService->delete($textId);
                    break;
                case 'update': {
                    if (!$text) {
                        return new JsonResponse(['error' => "Text with id {$textId} not found"], Response::HTTP_NOT_FOUND);
                    }
                    $indexService->update(new ElasticTextResource($text));
                    break;
                }
                case 'create':
                    if (!$text) {
                        return new JsonResponse(['error' => "Text with id {$textId} not found"], Response::HTTP_NOT_FOUND);
                    }
                    $indexService->add(new ElasticTextResource($text));
                    break;
            }
            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}
