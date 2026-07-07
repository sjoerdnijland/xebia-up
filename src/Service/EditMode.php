<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class EditMode
{
    private const KEY = 'edit_mode';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly string $adminKey = '',
    ) {
    }

    public function isOn(): bool
    {
        return (bool) ($this->session()?->get(self::KEY, false));
    }

    public function enableWith(string $key): bool
    {
        if ($this->adminKey === '' || $key !== $this->adminKey) {
            return false;
        }
        $this->session()?->set(self::KEY, true);
        return true;
    }

    public function disable(): void
    {
        $this->session()?->remove(self::KEY);
    }

    private function session(): ?SessionInterface
    {
        $req = $this->requestStack->getMainRequest();
        return $req && $req->hasSession() ? $req->getSession() : null;
    }
}
