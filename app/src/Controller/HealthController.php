<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class HealthController extends BaseController
{
    /**
     * @Route("/health", name="health", methods={"GET"})
     */
    public function health(Request $request): JsonResponse
    {
        return new JsonResponse(['status' => 'ok']);
    }
}
