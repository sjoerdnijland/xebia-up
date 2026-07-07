<?php

namespace App\Controller;

use App\Entity\ModuleCapability;
use App\Repository\CategoryRepository;
use App\Repository\LevelRepository;
use App\Repository\ModuleCapabilityRepository;
use App\Repository\ModuleRepository;
use App\Repository\RoleRepository;
use App\Repository\SkillRepository;
use App\Service\CapabilityMap;
use App\Service\EditMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

class AdminController extends AbstractController
{
    public function __construct(
        private readonly EditMode $editMode,
    ) {
    }

    #[Route('/admin/enable', name: 'admin_enable', methods: ['GET'])]
    public function enable(Request $request): Response
    {
        $key = (string) $request->query->get('key', '');
        if (!$this->editMode->enableWith($key)) {
            throw new AccessDeniedHttpException('Invalid admin key');
        }
        return $this->redirect($request->headers->get('referer', '/'));
    }

    #[Route('/admin/disable', name: 'admin_disable', methods: ['GET'])]
    public function disable(Request $request): Response
    {
        $this->editMode->disable();
        return $this->redirect($request->headers->get('referer', '/'));
    }

    #[Route('/admin/modules/{slug}', name: 'admin_module_update', methods: ['POST'])]
    public function updateModule(
        string $slug,
        Request $request,
        ModuleRepository $moduleRepo,
        LevelRepository $levelRepo,
        CategoryRepository $categoryRepo,
        RoleRepository $roleRepo,
        SkillRepository $skillRepo,
        ModuleCapabilityRepository $mcRepo,
        EntityManagerInterface $em,
    ): JsonResponse {
        if (!$this->editMode->isOn()) {
            return $this->json(['ok' => false, 'error' => 'Edit mode is off'], 403);
        }

        $module = $moduleRepo->findOneBy(['slug' => $slug]);
        if (!$module) {
            return $this->json(['ok' => false, 'error' => 'Module not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['ok' => false, 'error' => 'Invalid JSON'], 400);
        }

        // --- Scalars ---
        if (isset($data['title']) && is_string($data['title']) && trim($data['title']) !== '') {
            $module->setTitle(trim($data['title']));
        }
        if (isset($data['description']) && is_string($data['description'])) {
            $module->setDescription(trim($data['description']));
        }

        // --- Level ---
        if (isset($data['level_slug']) && is_string($data['level_slug'])) {
            $level = $levelRepo->findOneBy(['slug' => $data['level_slug']]);
            if (!$level) {
                return $this->json(['ok' => false, 'error' => 'Unknown level slug'], 400);
            }
            $module->setLevel($level);
        }

        // --- Categories (M2M, replace) ---
        if (isset($data['category_slugs']) && is_array($data['category_slugs'])) {
            $wantCats = array_values(array_unique(array_filter(array_map('strval', $data['category_slugs']))));
            if (empty($wantCats)) {
                return $this->json(['ok' => false, 'error' => 'At least one category required'], 400);
            }
            $newCats = [];
            foreach ($wantCats as $catSlug) {
                $cat = $categoryRepo->findOneBy(['slug' => $catSlug]);
                if (!$cat) {
                    return $this->json(['ok' => false, 'error' => "Unknown category: $catSlug"], 400);
                }
                $newCats[$catSlug] = $cat;
            }
            // Remove currently-set categories not in the new list
            foreach ($module->getCategories()->toArray() as $existing) {
                if (!isset($newCats[$existing->getSlug()])) {
                    $module->getCategories()->removeElement($existing);
                }
            }
            // Add new ones
            foreach ($newCats as $cat) {
                $module->addCategory($cat);
            }
        }

        // --- Roles (M2M, replace) ---
        if (isset($data['role_slugs']) && is_array($data['role_slugs'])) {
            $wantRoles = array_values(array_unique(array_filter(array_map('strval', $data['role_slugs']))));
            $newRoles = [];
            foreach ($wantRoles as $rSlug) {
                $r = $roleRepo->findOneBy(['slug' => $rSlug]);
                if (!$r) {
                    return $this->json(['ok' => false, 'error' => "Unknown role: $rSlug"], 400);
                }
                $newRoles[$rSlug] = $r;
            }
            foreach ($module->getRoles()->toArray() as $existing) {
                if (!isset($newRoles[$existing->getSlug()])) {
                    $module->getRoles()->removeElement($existing);
                }
            }
            foreach ($newRoles as $r) {
                $module->addRole($r);
            }
        }

        // --- Skills (M2M, replace) ---
        if (isset($data['skill_slugs']) && is_array($data['skill_slugs'])) {
            $wantSkills = array_values(array_unique(array_filter(array_map('strval', $data['skill_slugs']))));
            $newSkills = [];
            foreach ($wantSkills as $sSlug) {
                $sk = $skillRepo->findOneBy(['slug' => $sSlug]);
                if (!$sk) {
                    return $this->json(['ok' => false, 'error' => "Unknown skill: $sSlug"], 400);
                }
                $newSkills[$sSlug] = $sk;
            }
            foreach ($module->getSkills()->toArray() as $existing) {
                if (!isset($newSkills[$existing->getSlug()])) {
                    $module->getSkills()->removeElement($existing);
                }
            }
            foreach ($newSkills as $sk) {
                $module->addSkill($sk);
            }
        }

        // Flush pre-capability changes so category M2M is up to date before
        // we validate capability rows.
        $em->flush();

        // --- Capabilities per category ---
        if (isset($data['capabilities']) && is_array($data['capabilities'])) {
            $currentCatSlugs = [];
            foreach ($module->getCategories() as $c) {
                $currentCatSlugs[$c->getSlug()] = $c;
            }

            // Fetch existing module_capability rows for this module.
            $existing = $em->getRepository(ModuleCapability::class)->findBy(['module' => $module]);
            $existingByCatSlug = [];
            foreach ($existing as $row) {
                $existingByCatSlug[$row->getCategory()->getSlug()] = $row;
            }

            // Upsert requested capabilities.
            foreach ($data['capabilities'] as $catSlug => $capKey) {
                if (!isset($currentCatSlugs[$catSlug])) {
                    continue; // ignore capabilities for categories the module is not in
                }
                $validKeys = CapabilityMap::CAPABILITY_KEYS_BY_CATEGORY[$catSlug] ?? [];
                if (!in_array($capKey, $validKeys, true)) {
                    return $this->json(['ok' => false, 'error' => "Invalid capability '$capKey' for category '$catSlug'"], 400);
                }
                if (isset($existingByCatSlug[$catSlug])) {
                    $existingByCatSlug[$catSlug]->setCapabilityKey($capKey);
                } else {
                    $row = (new ModuleCapability())
                        ->setModule($module)
                        ->setCategory($currentCatSlugs[$catSlug])
                        ->setCapabilityKey($capKey);
                    $em->persist($row);
                }
            }

            // Remove capability rows for categories the module is no longer in.
            foreach ($existingByCatSlug as $catSlug => $row) {
                if (!isset($currentCatSlugs[$catSlug])) {
                    $em->remove($row);
                }
            }
        }

        $em->flush();

        return $this->json(['ok' => true, 'slug' => $module->getSlug()]);
    }
}
