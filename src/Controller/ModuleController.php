<?php

namespace App\Controller;

use App\Repository\ModuleRepository;
use App\Repository\RoleRepository;
use App\Service\AiCapabilityMap;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ModuleController extends AbstractController
{
    #[Route('/modules/{slug}', name: 'module_show', methods: ['GET'])]
    public function show(
        string $slug,
        Request $request,
        ModuleRepository $moduleRepo,
        RoleRepository $roleRepo,
        AiCapabilityMap $capabilityMap,
    ): Response {
        $module = $moduleRepo->findOneBy(['slug' => $slug]);
        if (!$module) {
            throw $this->createNotFoundException('Module not found');
        }

        $totalRoles = $roleRepo->count([]);
        $inline = $request->query->getBoolean('inline');

        return $this->render('module/_detail.html.twig', [
            'module' => $module,
            'totalRoles' => $totalRoles,
            'inline' => $inline,
            'capabilities' => $capabilityMap->all(),
        ]);
    }
}
