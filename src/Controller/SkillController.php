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

        return $this->render('skills/show.html.twig', [
            'category' => $cat,
            'categories' => $allCategories,
            'activeCategory' => $cat->getSlug(),
            'levels' => $levels,
            'rings' => $rings,
            'capabilities' => $capabilityMap->allForCategory('ai'),
        ]);
    }
}
