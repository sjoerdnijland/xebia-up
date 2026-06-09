<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\LevelRepository;
use App\Repository\ModuleRepository;
use App\Repository\ModuleTypeRepository;
use App\Repository\RoleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class JourneyController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->redirectToRoute('journey_show', ['category' => 'ai']);
    }

    #[Route('/journeys/{category}', name: 'journey_show')]
    public function show(
        string $category,
        Request $request,
        CategoryRepository $categoryRepo,
        LevelRepository $levelRepo,
        RoleRepository $roleRepo,
        ModuleRepository $moduleRepo,
        ModuleTypeRepository $typeRepo,
    ): Response {
        $cat = $categoryRepo->findOneBy(['slug' => $category]);
        if (!$cat) {
            throw $this->createNotFoundException('Category not found');
        }

        $roleSlug = $request->query->get('role');
        $activeRole = $roleSlug ? $roleRepo->findOneBy(['slug' => $roleSlug]) : null;

        $typeSlug = $request->query->get('type');
        $activeType = $typeSlug ? $typeRepo->findOneBy(['slug' => $typeSlug]) : null;

        $allCategories = $categoryRepo->findAllOrdered();
        $levels = $levelRepo->findAllOrdered();
        $roles = $roleRepo->findAllOrdered();
        $types = $typeRepo->findAllOrdered();
        $modules = $moduleRepo->findByCategory($cat, $activeRole, $activeType);
        $roleCounts = $moduleRepo->countByCategoryAndRole($cat);
        $typeCounts = $moduleRepo->countByCategoryAndType($cat);

        // Group modules by level
        $byLevel = [];
        foreach ($levels as $level) {
            $byLevel[$level->getSlug()] = [];
        }
        foreach ($modules as $module) {
            $byLevel[$module->getLevel()->getSlug()][] = $module;
        }

        $totalRoles = count($roles);

        return $this->render('journey/show.html.twig', [
            'category' => $cat,
            'categories' => $allCategories,
            'levels' => $levels,
            'roles' => $roles,
            'types' => $types,
            'byLevel' => $byLevel,
            'activeRole' => $activeRole,
            'activeType' => $activeType,
            'roleCounts' => $roleCounts,
            'typeCounts' => $typeCounts,
            'totalModules' => count($modules),
            'totalRoles' => $totalRoles,
        ]);
    }
}
