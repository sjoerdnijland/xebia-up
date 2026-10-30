<?php

namespace App\Controller;

use App\Entity\Skill;
use App\Repository\CategoryRepository;
use App\Repository\LevelRepository;
use App\Repository\ModuleRepository;
use App\Repository\SkillRepository;
use App\Service\CapabilityMap;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SkillController extends AbstractController
{
    private const RING_ORDER = [
        'human-core'      => 1,
        'working-with-ai' => 2,
        'leading-with-ai' => 3,
        'enabler'         => 4,
    ];

    #[Route('/skill/{slug}', name: 'skill_detail', methods: ['GET'])]
    public function detail(
        string $slug,
        Request $request,
        SkillRepository $skillRepo,
        LevelRepository $levelRepo,
        ModuleRepository $moduleRepo,
        CapabilityMap $capabilityMap,
    ): Response {
        $skill = $skillRepo->findOneBy(['slug' => $slug]);
        if (!$skill) {
            throw $this->createNotFoundException('Skill not found');
        }

        $fromSlug = $request->query->get('from');
        $fromModule = $fromSlug ? $moduleRepo->findOneBy(['slug' => $fromSlug]) : null;

        return $this->render('skills/_detail.html.twig', [
            'skill' => $skill,
            'levels' => $levelRepo->findAllOrdered(),
            'capabilities' => $capabilityMap->allForCategory('ai'),
            'fromModule' => $fromModule,
            'inline' => $request->query->getBoolean('inline'),
        ]);
    }

    #[Route('/skills/{category}', name: 'skill_show')]
    public function show(
        string $category,
        CategoryRepository $categoryRepo,
        LevelRepository $levelRepo,
        SkillRepository $skillRepo,
        CapabilityMap $capabilityMap,
    ): Response {
        $cat = $categoryRepo->findOneBy(['slug' => $category]);
        if (!$cat) {
            throw $this->createNotFoundException('Category not found');
        }

        $allCategories = $categoryRepo->findAllOrdered();
        $levels = $levelRepo->findAllOrdered();
        $skills = $skillRepo->findByCategoryOrdered($cat);

        // Group: ring → domain → skills
        $rings = [];
        foreach ($skills as $skill) {
            $rs = $skill->getRingSlug();
            $ds = $skill->getDomainSlug();
            if (!isset($rings[$rs])) {
                $rings[$rs] = [
                    'slug' => $rs,
                    'name' => $skill->getRingName(),
                    'order' => self::RING_ORDER[$rs] ?? 99,
                    'domains' => [],
                ];
            }
            if (!isset($rings[$rs]['domains'][$ds])) {
                $rings[$rs]['domains'][$ds] = [
                    'slug' => $ds,
                    'name' => $skill->getDomainName(),
                    'capabilityKey' => $skill->getCapabilityKey(),
                    'skills' => [],
                ];
            }
            $rings[$rs]['domains'][$ds]['skills'][] = $skill;
        }

        // Sort rings by canonical order
        uasort($rings, fn($a, $b) => $a['order'] <=> $b['order']);

        $capabilities = $capabilityMap->allForCategory('ai');

        return $this->render('skills/show.html.twig', [
            'category' => $cat,
            'categories' => $allCategories,
            'activeCategory' => $cat->getSlug(),
            'levels' => $levels,
            'rings' => $rings,
            'capabilities' => $capabilities,
            'selectorData' => $this->buildSelectorData($levels, $rings, $capabilities),
        ]);
    }

    /**
     * Data for the interactive "select skills & levels" mode on the model page:
     * every skill with its level descriptions, plus the active modules linked to it.
     * The client side matches modules to a selected target level.
     *
     * @param \App\Entity\Level[] $levels
     */
    private function buildSelectorData(array $levels, array $rings, array $capabilities): array
    {
        $modules = [];
        $skillData = [];
        $order = 0;

        foreach ($rings as $ring) {
            foreach ($ring['domains'] as $domain) {
                foreach ($domain['skills'] as $skill) {
                    /** @var Skill $skill */
                    $moduleSlugs = [];
                    foreach ($skill->getModules() as $module) {
                        if (!$module->isActive()) {
                            continue;
                        }
                        $slug = $module->getSlug();
                        if (!isset($modules[$slug])) {
                            $level = $module->getLevel();
                            $modules[$slug] = [
                                'slug' => $slug,
                                'title' => $module->getTitle(),
                                'description' => $module->getDescription(),
                                'depth' => $level->getDepth(),
                                'level' => $level->getSlug(),
                                'hours' => $module->getDurationHours(),
                                'duration' => $module->getFormattedDuration(),
                                'type' => $module->getType()?->getName(),
                                'position' => $module->getPosition(),
                            ];
                        }
                        $moduleSlugs[] = $slug;
                    }

                    $cap = $capabilities[$skill->getCapabilityKey()] ?? null;
                    $skillData[$skill->getSlug()] = [
                        'slug' => $skill->getSlug(),
                        'name' => $skill->getName(),
                        'domain' => $skill->getDomainName(),
                        'domainSlug' => $skill->getDomainSlug(),
                        'ring' => $skill->getRingName(),
                        'color' => $cap['color'] ?? '#7C1B7B',
                        'order' => $order++,
                        'descriptions' => $skill->getDescriptions(),
                        'modules' => $moduleSlugs,
                    ];
                }
            }
        }

        return [
            'levels' => array_map(fn($l) => [
                'slug' => $l->getSlug(),
                'name' => $l->getName(),
                'depth' => $l->getDepth(),
                'color' => $l->getColorHex(),
            ], $levels),
            'skills' => $skillData,
            'modules' => $modules,
        ];
    }
}
