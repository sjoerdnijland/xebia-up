<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SiteAccessController extends AbstractController
{
    public function __construct(
        private readonly string $siteAccessPassword,
    ) {
    }

    #[Route('/__access', name: 'site_access_login', methods: ['GET', 'POST'])]
    public function login(Request $request): Response
    {
        $error = null;

        if ($request->isMethod('POST')) {
            $submitted = (string) $request->request->get('password', '');
            if (hash_equals($this->siteAccessPassword, $submitted)) {
                $request->getSession()->set('site_access_granted', true);

                $next = (string) $request->query->get('next', '/');
                if ($next === '' || !str_starts_with($next, '/') || str_starts_with($next, '//')) {
                    $next = '/';
                }
                return $this->redirect($next);
            }
            $error = 'Incorrect password.';
        }

        return $this->render('site_access/login.html.twig', [
            'error' => $error,
            'next' => $request->query->get('next', '/'),
        ]);
    }
}
