<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Journey;
use App\Repository\CategoryRepository;
use App\Repository\ModuleRepository;
use App\Service\AiCapabilityMap;
use App\Service\JourneyBuilder;
use App\Service\JourneyCollection;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

class JourneyBuilderController extends AbstractController
{
    public function __construct(
        private readonly JourneyBuilder $journey,
        private readonly JourneyCollection $journeys,
        private readonly bool $showSkillsOnOverview = false,
    ) {
    }

    /* ============================================================
       Global mode
       ============================================================ */

    #[Route('/mode/toggle', name: 'in_company_toggle', methods: ['POST'])]
    public function toggle(Request $request): Response
    {
        $this->journeys->toggleMode();
        return $this->redirectToSafeNext($request);
    }

    /* ============================================================
       Clients
       ============================================================ */

    #[Route('/clients/create', name: 'client_create', methods: ['POST'])]
    public function clientCreate(Request $request): Response
    {
        $name = (string) $request->request->get('name', '');
        try {
            $this->journeys->createClient($name);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }
        return $this->redirectToSafeNext($request, fallback: $this->generateUrl('journey_index'));
    }

    #[Route('/clients/{id}/rename', name: 'client_rename', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function clientRename(int $id, Request $request): Response
    {
        $name = (string) $request->request->get('name', '');
        $this->journeys->renameClient($id, $name);
        return $this->redirectToSafeNext($request, fallback: $this->generateUrl('journey_index'));
    }

    #[Route('/clients/{id}/delete', name: 'client_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function clientDelete(int $id, Request $request): Response
    {
        $this->journeys->removeClient($id);
        return $this->redirectToSafeNext($request, fallback: $this->generateUrl('journey_index'));
    }

    /* ============================================================
       Journeys
       ============================================================ */

    #[Route('/journeys', name: 'journey_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('journey/index.html.twig', [
            'clientGroups' => $this->journeys->groupedByClient(),
            'totalJourneys' => $this->journeys->count(),
        ]);
    }

    #[Route('/journeys/add-modal/{moduleSlug}', name: 'journey_add_modal', methods: ['GET'])]
    public function addModal(string $moduleSlug, ModuleRepository $moduleRepo): Response
    {
        $module = $moduleRepo->findOneBy(['slug' => $moduleSlug]);
        if (!$module) {
            throw $this->createNotFoundException('Module not found');
        }

        return $this->render('journey/_add_modal.html.twig', [
            'module' => $module,
            'clientGroups' => $this->journeys->groupedByClient(),
            'clients' => $this->journeys->allClients(),
            'containsThis' => $this->containsMap($moduleSlug),
        ]);
    }

    #[Route('/journeys/create', name: 'journey_create', methods: ['POST'])]
    public function create(Request $request, ModuleRepository $moduleRepo): Response
    {
        $name = (string) $request->request->get('name', '');
        $audience = (string) $request->request->get('audience', '');
        $clientId = $request->request->get('clientId');
        $clientId = ($clientId === '' || $clientId === null) ? null : (int) $clientId;
        $clientName = (string) $request->request->get('clientName', '');

        try {
            $journey = $this->journeys->create($name, $audience, $clientId, $clientName ?: null);
        } catch (\InvalidArgumentException $e) {
            if ($this->expectsJson($request)) {
                return new JsonResponse(['error' => $e->getMessage()], 400);
            }
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToSafeNext($request, fallback: $this->generateUrl('journey_index'));
        }

        // Optional chained "create-then-add" used by the modal.
        $then = (string) $request->request->get('then', '');
        $slug = (string) $request->request->get('slug', '');
        if ($then === 'select' && $slug !== '') {
            if ($moduleRepo->findOneBy(['slug' => $slug])) {
                $this->journeys->addSlugTo($journey->getId(), $slug);
            }
        }

        return $this->modalResponse($request, $moduleRepo, $slug !== '' ? $slug : null);
    }

    #[Route('/journeys/{id}/select', name: 'journey_id_select', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function selectInto(int $id, Request $request, ModuleRepository $moduleRepo): Response
    {
        $slug = (string) $request->request->get('slug', '');
        if ($slug !== '' && $moduleRepo->findOneBy(['slug' => $slug])) {
            $this->journeys->addSlugTo($id, $slug);
        }
        return $this->modalResponse($request, $moduleRepo, $slug !== '' ? $slug : null);
    }

    #[Route('/journeys/{id}/deselect', name: 'journey_id_deselect', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deselectFrom(int $id, Request $request, ModuleRepository $moduleRepo): Response
    {
        $slug = (string) $request->request->get('slug', '');
        if ($slug !== '') {
            $this->journeys->removeSlugFrom($id, $slug);
        }
        return $this->modalResponse($request, $moduleRepo, $slug !== '' ? $slug : null);
    }

    #[Route('/journeys/{id}/rename', name: 'journey_id_rename', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function renameJourney(int $id, Request $request): Response
    {
        $name = $request->request->has('name')
            ? (string) $request->request->get('name', '')
            : null;
        $audience = $request->request->has('audience')
            ? (string) $request->request->get('audience', '')
            : null;
        $clientId = null;
        if ($request->request->has('clientId')) {
            $val = $request->request->get('clientId');
            $clientId = ($val === '' || $val === null) ? null : (int) $val;
        }
        $this->journeys->rename($id, $name, $audience, $clientId);

        if ($this->expectsJson($request)) {
            $j = $this->journeys->get($id);
            return new JsonResponse([
                'id' => $id,
                'name' => $j?->getName(),
                'audience' => $j?->getAudience(),
                'clientId' => $j?->getClient()->getId(),
                'clientName' => $j?->getClient()->getName(),
            ]);
        }
        return $this->redirectToSafeNext($request, fallback: $this->generateUrl('journey_index'));
    }

    #[Route('/journeys/{id}/delete', name: 'journey_id_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteJourney(int $id, Request $request): Response
    {
        $this->journeys->remove($id);
        return $this->redirectToSafeNext($request, fallback: $this->generateUrl('journey_index'));
    }

    #[Route('/journeys/{id}/activate', name: 'journey_id_activate', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function activateJourney(int $id, Request $request): Response
    {
        $this->journeys->setActive($id);
        return $this->redirectToSafeNext($request, fallback: $this->generateUrl('journey_detail', ['id' => $id]));
    }

    #[Route('/journeys/{id}/reorder', name: 'journey_id_reorder', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function reorderJourney(int $id, Request $request): Response
    {
        $slugs = $request->request->all('slugs');
        $this->journeys->reorderWithin($id, is_array($slugs) ? $slugs : []);

        if ($this->expectsJson($request)) {
            $j = $this->journeys->get($id);
            return new JsonResponse([
                'id' => $id,
                'slugs' => $j?->getModuleSlugs() ?? [],
            ]);
        }
        return $this->redirectToSafeNext($request, fallback: $this->generateUrl('journey_detail', ['id' => $id]));
    }

    #[Route('/journeys/{id}', name: 'journey_detail', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function detail(
        int $id,
        ModuleRepository $moduleRepo,
        CategoryRepository $categoryRepo,
        AiCapabilityMap $capabilityMap,
    ): Response {
        $journey = $this->journeys->get($id);
        if (!$journey) {
            throw $this->createNotFoundException('Journey not found');
        }
        $this->journeys->setActive($id);

        $modulesInOrder = $this->modulesInOrder($journey, $moduleRepo);

        return $this->render('journey/detail.html.twig', [
            'currentJourney' => $journey,
            'categories' => $categoryRepo->findAllOrdered(),
            'modulesInOrder' => $modulesInOrder,
            'totalSelected' => $journey->count(),
            'capabilities' => $capabilityMap->all(),
            'showSkills' => $this->showSkillsOnOverview,
            'clients' => $this->journeys->allClients(),
        ]);
    }

    #[Route('/journeys/{id}/pdf', name: 'journey_id_pdf', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function detailPdf(
        int $id,
        ModuleRepository $moduleRepo,
        CategoryRepository $categoryRepo,
        AiCapabilityMap $capabilityMap,
    ): Response {
        $journey = $this->journeys->get($id);
        if (!$journey) {
            throw $this->createNotFoundException('Journey not found');
        }
        return $this->renderJourneyPdf($journey, $moduleRepo, $categoryRepo, $capabilityMap);
    }

    /* ============================================================
       Legacy routes — kept as compat (active-journey targets)
       ============================================================ */

    #[Route('/journey/select', name: 'journey_select', methods: ['POST'])]
    public function legacySelect(Request $request, ModuleRepository $moduleRepo): Response
    {
        $slug = (string) $request->request->get('slug', '');
        if ($slug !== '' && $moduleRepo->findOneBy(['slug' => $slug])) {
            $this->journey->select($slug);
        }
        return $this->legacyAck($request);
    }

    #[Route('/journey/deselect', name: 'journey_deselect', methods: ['POST'])]
    public function legacyDeselect(Request $request): Response
    {
        $slug = (string) $request->request->get('slug', '');
        if ($slug !== '') {
            $this->journey->deselect($slug);
        }
        return $this->legacyAck($request);
    }

    #[Route('/journey/clear', name: 'journey_clear', methods: ['POST'])]
    public function legacyClear(Request $request): Response
    {
        $this->journey->clear();
        return $this->redirectToSafeNext($request, fallback: $this->generateUrl('journey_index'));
    }

    #[Route('/journey/reorder', name: 'journey_reorder', methods: ['POST'])]
    public function legacyReorder(Request $request): Response
    {
        $slugs = $request->request->all('slugs');
        if (!is_array($slugs)) {
            $slugs = [];
        }
        $this->journey->reorder($slugs);

        if ($this->expectsJson($request)) {
            return new JsonResponse([
                'selectedSlugs' => $this->journey->getSelectedSlugs(),
                'count' => $this->journey->count(),
            ]);
        }
        return $this->redirectToSafeNext($request, fallback: $this->generateUrl('journey_index'));
    }

    #[Route('/journey/selection', name: 'journey_selection', methods: ['GET'])]
    public function legacySelection(): Response
    {
        $active = $this->journeys->getActive();
        if ($active) {
            return $this->redirectToRoute('journey_detail', ['id' => $active->getId()]);
        }
        return $this->redirectToRoute('journey_index');
    }

    #[Route('/journey/selection/pdf', name: 'journey_selection_pdf', methods: ['GET'])]
    public function legacySelectionPdf(
        ModuleRepository $moduleRepo,
        CategoryRepository $categoryRepo,
        AiCapabilityMap $capabilityMap,
    ): Response {
        $active = $this->journeys->getActive();
        if (!$active) {
            return $this->redirectToRoute('journey_index');
        }
        return $this->renderJourneyPdf($active, $moduleRepo, $categoryRepo, $capabilityMap);
    }

    /* ============================================================
       Helpers
       ============================================================ */

    private function modalResponse(Request $request, ModuleRepository $moduleRepo, ?string $slug): Response
    {
        if (!$this->expectsJson($request)) {
            return $this->redirectToSafeNext($request, fallback: $this->generateUrl('journey_index'));
        }

        $payload = [
            'count' => $this->journeys->count(),
            'totalModules' => $this->journeys->totalModules(),
        ];

        if ($slug !== null) {
            $payload['slug'] = $slug;
            $payload['anyJourneys'] = array_map(
                static fn (Journey $j) => $j->getId(),
                $this->journeys->journeysContainingSlug($slug),
            );

            $module = $moduleRepo->findOneBy(['slug' => $slug]);
            if ($module) {
                $payload['html'] = $this->renderView('journey/_add_modal.html.twig', [
                    'module' => $module,
                    'clientGroups' => $this->journeys->groupedByClient(),
                    'clients' => $this->journeys->allClients(),
                    'containsThis' => $this->containsMap($slug),
                ]);
            }
        }

        return new JsonResponse($payload);
    }

    private function legacyAck(Request $request): Response
    {
        if ($this->expectsJson($request)) {
            return new JsonResponse([
                'selectedSlugs' => $this->journey->getSelectedSlugs(),
                'count' => $this->journey->count(),
            ]);
        }
        return $this->redirectToSafeNext($request);
    }

    /**
     * @return array<int, bool> journey id → does this journey contain $slug
     */
    private function containsMap(string $slug): array
    {
        $out = [];
        foreach ($this->journeys->ordered() as $j) {
            $out[$j->getId()] = $j->containsSlug($slug);
        }
        return $out;
    }

    /**
     * Modules in $journey, indexed by category slug, preserving the journey's slug order.
     *
     * @return array{0: array<string, \App\Entity\Module[]>}
     */
    private function modulesByCategory(Journey $journey, ModuleRepository $moduleRepo): array
    {
        $modules = $this->modulesInOrder($journey, $moduleRepo);

        $byCategory = [];
        foreach ($modules as $module) {
            $cat = $module->getCategories()->first();
            if (!$cat) {
                continue;
            }
            $byCategory[$cat->getSlug()][] = $module;
        }
        return [$byCategory];
    }

    /**
     * Modules in $journey as a flat array, preserving the journey's slug order.
     *
     * @return \App\Entity\Module[]
     */
    private function modulesInOrder(Journey $journey, ModuleRepository $moduleRepo): array
    {
        $slugs = $journey->getModuleSlugs();
        if (!$slugs) {
            return [];
        }
        $modules = $moduleRepo->findBy(['slug' => $slugs]);
        $bySlug = [];
        foreach ($modules as $m) {
            $bySlug[$m->getSlug()] = $m;
        }
        $out = [];
        foreach ($slugs as $slug) {
            if (isset($bySlug[$slug])) {
                $out[] = $bySlug[$slug];
            }
        }
        return $out;
    }

    private function renderJourneyPdf(
        Journey $journey,
        ModuleRepository $moduleRepo,
        CategoryRepository $categoryRepo,
        AiCapabilityMap $capabilityMap,
    ): Response {
        $modulesInOrder = $this->modulesInOrder($journey, $moduleRepo);
        $clientName = $journey->getClient()->getName();

        $logoPath = $this->getParameter('kernel.project_dir') . '/public/img/xebia-logo.svg';
        $logoSrc = '';
        if (is_readable($logoPath)) {
            $svg = (string) file_get_contents($logoPath);
            $svg = str_replace('currentColor', '#561257', $svg);
            $logoSrc = 'data:image/svg+xml;base64,' . base64_encode($svg);
        }

        $html = $this->renderView('journey/selection_pdf.html.twig', [
            'modulesInOrder' => $modulesInOrder,
            'totalSelected' => $journey->count(),
            'clientName' => $clientName,
            'roleName' => $journey->getName(),
            'audience' => $journey->getAudience(),
            'generatedAt' => new \DateTimeImmutable(),
            'logoSrc' => $logoSrc,
            'capabilities' => $capabilityMap->all(),
        ]);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'sans-serif');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $slugger = new AsciiSlugger();
        $fileSlug = $slugger->slug(trim($clientName . ' ' . $journey->getName()))->lower()->toString();
        $filename = sprintf('xebia-up-%s-%s.pdf', $fileSlug ?: 'journey', date('Y-m-d'));

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            ],
        );
    }

    private function expectsJson(Request $request): bool
    {
        return $request->isXmlHttpRequest()
            || str_contains((string) $request->headers->get('Accept'), 'application/json');
    }

    private function redirectToSafeNext(Request $request, ?string $fallback = null): RedirectResponse
    {
        $next = (string) ($request->request->get('_next')
            ?? $request->headers->get('referer')
            ?? '');
        if ($next !== '' && str_starts_with($next, '/') && !str_starts_with($next, '//')) {
            return new RedirectResponse($next);
        }
        $host = $request->getSchemeAndHttpHost();
        if ($next !== '' && str_starts_with($next, $host)) {
            return new RedirectResponse($next);
        }
        return new RedirectResponse($fallback ?? '/');
    }
}
