<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ModuleRepository;
use App\Service\JourneyBuilder;
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
    public function __construct(private readonly JourneyBuilder $journey)
    {
    }

    #[Route('/mode/toggle', name: 'in_company_toggle', methods: ['POST'])]
    public function toggle(Request $request): Response
    {
        $this->journey->toggleMode();
        return $this->redirectToSafeNext($request);
    }

    #[Route('/journey/select', name: 'journey_select', methods: ['POST'])]
    public function select(Request $request, ModuleRepository $moduleRepo): Response
    {
        $slug = (string) $request->request->get('slug', '');
        if ($slug !== '' && $moduleRepo->findOneBy(['slug' => $slug])) {
            $this->journey->select($slug);
        }
        return $this->ackOrRedirect($request);
    }

    #[Route('/journey/deselect', name: 'journey_deselect', methods: ['POST'])]
    public function deselect(Request $request): Response
    {
        $slug = (string) $request->request->get('slug', '');
        if ($slug !== '') {
            $this->journey->deselect($slug);
        }
        return $this->ackOrRedirect($request);
    }

    #[Route('/journey/clear', name: 'journey_clear', methods: ['POST'])]
    public function clear(Request $request): Response
    {
        $this->journey->clear();
        return $this->redirectToSafeNext($request, fallback: $this->generateUrl('journey_selection'));
    }

    #[Route('/journey/client', name: 'journey_client', methods: ['POST'])]
    public function client(Request $request): Response
    {
        $name = (string) $request->request->get('name', '');
        $this->journey->setClientName($name);

        if ($this->expectsJson($request)) {
            return new JsonResponse(['clientName' => $this->journey->getClientName()]);
        }
        return $this->redirectToSafeNext($request, fallback: $this->generateUrl('journey_selection'));
    }

    #[Route('/journey/role', name: 'journey_role', methods: ['POST'])]
    public function role(Request $request): Response
    {
        $name = (string) $request->request->get('name', '');
        $this->journey->setRoleName($name);

        if ($this->expectsJson($request)) {
            return new JsonResponse(['roleName' => $this->journey->getRoleName()]);
        }
        return $this->redirectToSafeNext($request, fallback: $this->generateUrl('journey_selection'));
    }

    #[Route('/journey/reorder', name: 'journey_reorder', methods: ['POST'])]
    public function reorder(Request $request): Response
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
        return $this->redirectToSafeNext($request, fallback: $this->generateUrl('journey_selection'));
    }

    #[Route('/journey/selection', name: 'journey_selection', methods: ['GET'])]
    public function selection(
        ModuleRepository $moduleRepo,
        CategoryRepository $categoryRepo,
    ): Response {
        $slugs = $this->journey->getSelectedSlugs();
        $modules = $slugs ? $moduleRepo->findBy(['slug' => $slugs]) : [];

        // Index modules by slug, then walk the ordered slug list to preserve user-defined order.
        $bySlug = [];
        foreach ($modules as $m) {
            $bySlug[$m->getSlug()] = $m;
        }

        $byCategory = [];
        foreach ($slugs as $slug) {
            $module = $bySlug[$slug] ?? null;
            if (!$module) {
                continue;
            }
            $cat = $module->getCategories()->first();
            if (!$cat) {
                continue;
            }
            $byCategory[$cat->getSlug()][] = $module;
        }

        return $this->render('journey/selection.html.twig', [
            'categories' => $categoryRepo->findAllOrdered(),
            'byCategory' => $byCategory,
            'totalSelected' => count($modules),
            'clientName' => $this->journey->getClientName(),
            'roleName' => $this->journey->getRoleName(),
        ]);
    }

    #[Route('/journey/selection/pdf', name: 'journey_selection_pdf', methods: ['GET'])]
    public function selectionPdf(
        ModuleRepository $moduleRepo,
        CategoryRepository $categoryRepo,
    ): Response {
        $slugs = $this->journey->getSelectedSlugs();
        $modules = $slugs ? $moduleRepo->findBy(['slug' => $slugs]) : [];

        $bySlug = [];
        foreach ($modules as $m) {
            $bySlug[$m->getSlug()] = $m;
        }

        $byCategory = [];
        foreach ($slugs as $slug) {
            $module = $bySlug[$slug] ?? null;
            if (!$module) {
                continue;
            }
            $cat = $module->getCategories()->first();
            if (!$cat) {
                continue;
            }
            $byCategory[$cat->getSlug()][] = $module;
        }

        $clientName = $this->journey->getClientName();
        $roleName = $this->journey->getRoleName();

        $logoPath = $this->getParameter('kernel.project_dir') . '/public/img/xebia-logo.svg';
        $logoSrc = '';
        if (is_readable($logoPath)) {
            $svg = (string) file_get_contents($logoPath);
            // dompdf doesn't propagate `currentColor` through SVG — bake in the brand colour.
            $svg = str_replace('currentColor', '#561257', $svg);
            $logoSrc = 'data:image/svg+xml;base64,' . base64_encode($svg);
        }

        $html = $this->renderView('journey/selection_pdf.html.twig', [
            'categories' => $categoryRepo->findAllOrdered(),
            'byCategory' => $byCategory,
            'totalSelected' => count($modules),
            'clientName' => $clientName,
            'roleName' => $roleName,
            'generatedAt' => new \DateTimeImmutable(),
            'logoSrc' => $logoSrc,
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
        $fileSlug = $slugger->slug(trim(($clientName ?: 'xebia-up') . ' ' . $roleName))->lower()->toString();
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

    private function ackOrRedirect(Request $request): Response
    {
        if ($this->expectsJson($request)) {
            return new JsonResponse([
                'selectedSlugs' => $this->journey->getSelectedSlugs(),
                'count' => $this->journey->count(),
            ]);
        }
        return $this->redirectToSafeNext($request);
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
        // Allow same-origin absolute URLs for referer
        $host = $request->getSchemeAndHttpHost();
        if ($next !== '' && str_starts_with($next, $host)) {
            return new RedirectResponse($next);
        }
        return new RedirectResponse($fallback ?? '/');
    }
}
