<?php

namespace App\Controller;

use App\Service\ElasticSearch\Index\TextIndexService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Repository\TextRepository;
use App\Resource\ElasticSearch\ElasticTextResource;
use App\Service\ElasticSearch\Base\IndexServiceInterface;
use App\Service\ElasticSearch\Search\TextSearchService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class WebhookController extends AbstractController {

    public function __construct(TextSearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * @Route("/webhook/reindex", name="webhook_reindex", methods={"POST"})
     *
     */
    public function reindex(Request $request, TextRepository $repository, TextIndexService $indexService): JsonResponse
    {
        // validate webhook key
        $apiKey = $request->headers->get('X-API-KEY') ?? null;
        if (!$apiKey) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $expectedKey =  $this->getParameter('reindexTextWebhook.apiKey') ?? null;
        if (!$expectedKey || $apiKey !== $expectedKey) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        // validate payload
        $payload = json_decode($request->getContent(), true);
        if (!isset($payload['primaryKey'], $payload['state'], $payload['table'])) {
            return new JsonResponse(['error' => 'Missing primaryKey or state'], Response::HTTP_BAD_REQUEST);
        }
        $state = in_array($payload['state'] ?? "unknown", ["create", "update", "delete"], TRUE) ? $payload['state'] : null;
        $primaryKey = intval($payload['primaryKey']);
        $tableName = $payload['table'];
        $data = $payload['data'] ?? [];

        if (!$state || !$primaryKey || !$tableName) {
            return new JsonResponse(['error' => 'Invalid primary key or state'], Response::HTTP_BAD_REQUEST);
        }

        // process request
        switch (strtolower($tableName)) {
            case 'text':
                try {
                    $text = $repository->find($primaryKey);

                    switch($state) {
                        case 'delete':
                            if ($text) {
                                return new JsonResponse(['error' => "Text with id {$primaryKey} exists in database"], Response::HTTP_NOT_FOUND);
                            }
                            $indexService->delete($primaryKey);
                            break;
                        case 'update': {
                            if (!$text) {
                                return new JsonResponse(['error' => "Text with id {$primaryKey} not found"], Response::HTTP_NOT_FOUND);
                            }
                            $indexService->update(new ElasticTextResource($text));
                            break;
                        }
                        case 'create':
                            if (!$text) {
                                return new JsonResponse(['error' => "Text with id {$primaryKey} not found"], Response::HTTP_NOT_FOUND);
                            }
                            $indexService->add(new ElasticTextResource($text));
                            break;
                    }
                    return new JsonResponse(['success' => true, 'reindexed' => 1, 'id' => [$primaryKey]]);
                } catch (\Exception $e) {
                    return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
                break;
            default:
                // check if text_id is provided in data for related record update
                if (array_key_exists('text_id', $data) && is_int($data['text_id']) && $data['text_id'] > 0) {
                    try {
                        $text = $repository->find($data['text_id']);
                        if (!$text) {
                            return new JsonResponse(['error' => "Text with id {$data['text_id']} not found"], Response::HTTP_NOT_FOUND);
                        }
                        $indexService->update(new ElasticTextResource($text));
                        return new JsonResponse(['success' => true, 'reindexed' => 1,'id' => [$data['text_id']]]);
                    } catch (\Exception $e) {
                        return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
                    }
                }

                // map table names to text model relation names
                $relationMapper = [
                    'author' => 'authors',
                    'text__author' => 'authors',
                    'work' => 'works',
                    'text__work' => 'works',
                    'text_type' => 'textTypes',
                    'text__text_type' => 'textTypes',
                    'referenced_genre' => 'referencedGenres',
                    'text__referenced_genre' => 'referencedGenres',
                    'referenced_person' => 'referencedPersons',
                    'text__referenced_person' => 'referencedPersons',
                    'referenced_work' => 'referencedWorks',
                    'text__referenced_work' => 'referencedWorks',
                ];

                // related record update?
                // state is not relevant here, we just need to reindex all texts related to the entity
                if (array_key_exists($tableName, $relationMapper)) {
                    try {
                        $relationName = $relationMapper[$tableName] ?? null;

                        $texts = $repository->defaultQuery()->whereRelation($relationName, "${tableName}.${tableName}_id", "=", $primaryKey)->get();

                        if ($texts->count() === 0) {
                            return new JsonResponse(['success' => true, 'reindexed' => 0, 'id' => []]);
                        }

                        $textResources = ElasticTextResource::collection($texts);
                        $indexService->updateMultiple($textResources);

                        return new JsonResponse(['success' => true, 'reindexed' => $texts->count(), 'id' => $texts->pluck('text_id')]);
                    } catch (\Exception $e) {
                        return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
                    }
                }

                return new JsonResponse(['error' => "Table {$tableName} is not supported"], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Route("/webhook/reindex/all", name="webhook_reindex_all", methods={"POST"})
     *
     */
    public function reindexAll(Request $request, TextRepository $repository, TextIndexService $indexService): JsonResponse
    {
        // validate webhook key
        $apiKey = $request->headers->get('X-API-KEY') ?? null;
        if (!$apiKey) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $indexName = $indexService->createNewIndex();

            $count = 0;
            $repository->indexQuery()->chunk(100,
                function ($texts) use ($indexService, &$count): bool {
                    $textResources = ElasticTextResource::collection($texts);
                    $indexService->addMultiple($textResources);
                    $count += $texts->count();
                    return true;
                });

            $indexService->switchToNewIndex($indexName);

            return new JsonResponse(['success' => true, 'reindexed' => $count]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}
