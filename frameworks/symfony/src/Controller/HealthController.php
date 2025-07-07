<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Kernel;

class HealthController extends AbstractController
{
    public function index(): JsonResponse
    {
        return $this->json([
            'status' => 'ok',
            'framework' => 'symfony',
            'version' => Kernel::VERSION,
            'timestamp' => date('c')
        ]);
    }
}