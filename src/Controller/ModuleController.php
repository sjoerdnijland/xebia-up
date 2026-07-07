<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\LevelRepository;
use App\Repository\ModuleCapabilityRepository;
use App\Repository\ModuleRepository;
use App\Repository\RoleRepository;
use App\Repository\SkillRepository;
use App\Service\CapabilityMap;
use App\Service\EditMode;
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
        LevelRepository $levelRepo,
        CategoryRepository $categoryRepo,
        SkillRepository $skillRepo,
        ModuleCapabilityRepository $mcRepo,
        CapabilityMap $capabilityMap,
        EditMode $editMode,
    ): Response {
        $module = $moduleRepo->findOneBy(['slug' => $slug]);
        if (!$module) {
            throw $this->createNotFoundException('Module not found');
        }

        $totalRoles = $roleRepo->count([]);
        $inline = $request->query->getBoolean('inline');

        $params = [
            'module' => $module,
            'totalRoles' => $totalRoles,
            'inline' => $inline,
            'capabilities' => $capabilityMap->allForCategory(
                $module->getCategories()->first()?->getSlug() ?? ''
            ),
        ];

        if ($editMode->isOn()) {
            $params['editable'] = true;
            $params['allLevels'] = $levelRepo->findAllOrdered();
            $params['allCategories'] = $categoryRepo->findAllOrdered();
            $params['allRoles'] = $roleRepo->findAllOrdered();
            $params['allSkills'] = $skillRepo->findAllOrdered();
            $params['capabilitiesByCategory'] = CapabilityMap::CAPABILITIES;
            $params['moduleCapabilities'] = $mcRepo->findAllForModule($module->getSlug());
        }

        return $this->render('module/_detail.html.twig', $params);
    }
}
