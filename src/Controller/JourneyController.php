<?php

namespace App\Controller;

use App\Entity\Module;
use App\Repository\CategoryRepository;
use App\Repository\LevelRepository;
use App\Repository\ModuleRepository;
use App\Repository\ModuleTypeRepository;
use App\Repository\RoleRepository;
use App\Service\AiCapabilityMap;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class JourneyController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(
        CategoryRepository $categoryRepo,
        ModuleRepository $moduleRepo,
        AiCapabilityMap $capabilityMap,
    ): Response {
        $categories = $categoryRepo->findAllOrdered();

        $topByCategory = [];
        $capByModule = [];
        foreach ($categories as $cat) {
            // findByCategory orders by level depth then position, so the first hit
            // per level is the lowest-positioned module at that level.
            $picksByLevel = [];
            foreach ($moduleRepo->findByCategory($cat) as $m) {
                $levelSlug = $m->getLevel()->getSlug();
                if (!isset($picksByLevel[$levelSlug])) {
                    $picksByLevel[$levelSlug] = $m;
                }
            }
            // Preserve depth order (the array is already in level-depth order from the query).
            $mods = array_values($picksByLevel);
            $topByCategory[$cat->getSlug()] = $mods;

            if ($cat->getSlug() === 'ai') {
                foreach ($mods as $m) {
                    $key = $capabilityMap->forModule($m->getSlug());
                    if ($key !== null) {
                        $capByModule[$m->getSlug()] = $key;
                    }
                }
            }
        }

        return $this->render('home.html.twig', [
            'categories' => $categories,
            'topByCategory' => $topByCategory,
            'capabilities' => $capabilityMap->all(),
            'moduleCapabilities' => $capByModule,
        ]);
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
        AiCapabilityMap $capabilityMap,
    ): Response {
        $cat = $categoryRepo->findOneBy(['slug' => $category]);
        if (!$cat) {
            throw $this->createNotFoundException('Category not found');
        }

        $isAi = $cat->getSlug() === 'ai';

        $activeRoles = $this->multiParam($request, 'role');
        $activeTypes = $this->multiParam($request, 'type');
        $activeLevels = $this->multiParam($request, 'level');
        $activeCapabilities = $isAi ? $this->multiParam($request, 'capability') : [];

        $allCategories = $categoryRepo->findAllOrdered();
        $levels = $levelRepo->findAllOrdered();
        $roles = $roleRepo->findAllOrdered();
        $types = $typeRepo->findAllOrdered();

        $allModules = $moduleRepo->findByCategory($cat);

        $moduleCapabilities = [];
        if ($isAi) {
            foreach ($allModules as $module) {
                $key = $capabilityMap->forModule($module->getSlug());
                if ($key !== null) {
                    $moduleCapabilities[$module->getSlug()] = $key;
                }
            }
        }

        $modules = array_values(array_filter(
            $allModules,
            fn(Module $m) => $this->matchesFilters(
                $m,
                $activeRoles,
                $activeTypes,
                $activeLevels,
                $activeCapabilities,
                $moduleCapabilities,
            ),
        ));

        $roleCounts = [];
        foreach ($roles as $role) {
            $roleCounts[$role->getSlug()] = 0;
        }
        $typeCounts = [];
        foreach ($types as $type) {
            $typeCounts[$type->getSlug()] = 0;
        }
        $levelCounts = [];
        foreach ($levels as $level) {
            $levelCounts[$level->getSlug()] = 0;
        }
        $capabilityCounts = [];
        if ($isAi) {
            foreach ($capabilityMap->all() as $key => $_) {
                $capabilityCounts[$key] = 0;
            }
        }
        foreach ($allModules as $module) {
            foreach ($module->getRoles() as $r) {
                $roleCounts[$r->getSlug()] = ($roleCounts[$r->getSlug()] ?? 0) + 1;
            }
            if ($module->getType()) {
                $slug = $module->getType()->getSlug();
                $typeCounts[$slug] = ($typeCounts[$slug] ?? 0) + 1;
            }
            $levelCounts[$module->getLevel()->getSlug()]
                = ($levelCounts[$module->getLevel()->getSlug()] ?? 0) + 1;
            if ($isAi && isset($moduleCapabilities[$module->getSlug()])) {
                $cap = $moduleCapabilities[$module->getSlug()];
                $capabilityCounts[$cap] = ($capabilityCounts[$cap] ?? 0) + 1;
            }
        }

        $byLevel = [];
        foreach ($levels as $level) {
            $byLevel[$level->getSlug()] = [];
        }
        foreach ($modules as $module) {
            $byLevel[$module->getLevel()->getSlug()][] = $module;
        }

        $totalRoles = count($roles);
        $capabilities = $isAi ? $capabilityMap->all() : [];

        $filtersActive = $activeRoles || $activeTypes || $activeLevels || $activeCapabilities;

        return $this->render('journey/show.html.twig', [
            'category' => $cat,
            'categories' => $allCategories,
            'levels' => $levels,
            'roles' => $roles,
            'types' => $types,
            'byLevel' => $byLevel,
            'activeRoles' => $activeRoles,
            'activeTypes' => $activeTypes,
            'activeLevels' => $activeLevels,
            'activeCapabilities' => $activeCapabilities,
            'roleCounts' => $roleCounts,
            'typeCounts' => $typeCounts,
            'levelCounts' => $levelCounts,
            'capabilityCounts' => $capabilityCounts,
            'totalModules' => count($modules),
            'totalRoles' => $totalRoles,
            'capabilities' => $capabilities,
            'moduleCapabilities' => $moduleCapabilities,
            'filtersActive' => $filtersActive,
        ]);
    }

    /**
     * Accept either ?key=slug (single, back-compat) or ?key[]=slug1&key[]=slug2.
     *
     * @return string[]
     */
    private function multiParam(Request $request, string $key): array
    {
        $all = $request->query->all();
        $value = $all[$key] ?? null;
        if (is_array($value)) {
            return array_values(array_filter(
                array_map('strval', $value),
                fn($v) => $v !== '',
            ));
        }
        if (is_string($value) && $value !== '') {
            return [$value];
        }
        return [];
    }

    private function matchesFilters(
        Module $m,
        array $roles,
        array $types,
        array $levels,
        array $capabilities,
        array $moduleCapabilities,
    ): bool {
        if ($levels && !in_array($m->getLevel()->getSlug(), $levels, true)) {
            return false;
        }
        if ($types) {
            $typeSlug = $m->getType()?->getSlug();
            if ($typeSlug === null || !in_array($typeSlug, $types, true)) {
                return false;
            }
        }
        if ($roles) {
            $moduleRoleSlugs = array_map(fn($r) => $r->getSlug(), $m->getRoles()->toArray());
            if (!array_intersect($roles, $moduleRoleSlugs)) {
                return false;
            }
        }
        if ($capabilities) {
            $cap = $moduleCapabilities[$m->getSlug()] ?? null;
            if ($cap === null || !in_array($cap, $capabilities, true)) {
                return false;
            }
        }
        return true;
    }
}
