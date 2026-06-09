<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class JourneyBuilder
{
    private const MODE_KEY = 'in_company_mode';
    private const SELECTION_KEY = 'in_company_selection';
    private const CLIENT_NAME_KEY = 'in_company_client_name';
    private const ROLE_NAME_KEY = 'in_company_role_name';

    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function isOn(): bool
    {
        return (bool) $this->session()?->get(self::MODE_KEY, false);
    }

    public function toggleMode(): bool
    {
        $session = $this->session();
        if (!$session) {
            return false;
        }
        $next = !$session->get(self::MODE_KEY, false);
        $session->set(self::MODE_KEY, $next);
        return $next;
    }

    /** @return string[] */
    public function getSelectedSlugs(): array
    {
        return $this->session()?->get(self::SELECTION_KEY, []) ?? [];
    }

    public function isSelected(string $slug): bool
    {
        return in_array($slug, $this->getSelectedSlugs(), true);
    }

    public function count(): int
    {
        return count($this->getSelectedSlugs());
    }

    public function select(string $slug): void
    {
        $slugs = $this->getSelectedSlugs();
        if (!in_array($slug, $slugs, true)) {
            $slugs[] = $slug;
            $this->session()?->set(self::SELECTION_KEY, $slugs);
        }
    }

    public function deselect(string $slug): void
    {
        $slugs = array_values(array_filter(
            $this->getSelectedSlugs(),
            static fn (string $s): bool => $s !== $slug,
        ));
        $this->session()?->set(self::SELECTION_KEY, $slugs);
    }

    public function clear(): void
    {
        $session = $this->session();
        if (!$session) {
            return;
        }
        $session->set(self::SELECTION_KEY, []);
        $session->set(self::ROLE_NAME_KEY, '');
    }

    public function getClientName(): string
    {
        return (string) ($this->session()?->get(self::CLIENT_NAME_KEY, '') ?? '');
    }

    public function setClientName(string $name): void
    {
        $name = trim($name);
        $this->session()?->set(self::CLIENT_NAME_KEY, mb_substr($name, 0, 120));
    }

    public function getRoleName(): string
    {
        return (string) ($this->session()?->get(self::ROLE_NAME_KEY, '') ?? '');
    }

    public function setRoleName(string $name): void
    {
        $name = trim($name);
        $this->session()?->set(self::ROLE_NAME_KEY, mb_substr($name, 0, 120));
    }

    /**
     * Reorder the selection to match the given slugs.
     * Any unknown slugs are dropped; any current slugs missing from $slugs are appended at the end
     * so a stale client doesn't accidentally lose modules.
     *
     * @param string[] $slugs
     */
    public function reorder(array $slugs): void
    {
        $current = $this->getSelectedSlugs();
        $clean = [];
        foreach ($slugs as $slug) {
            if (is_string($slug) && in_array($slug, $current, true) && !in_array($slug, $clean, true)) {
                $clean[] = $slug;
            }
        }
        foreach ($current as $slug) {
            if (!in_array($slug, $clean, true)) {
                $clean[] = $slug;
            }
        }
        $this->session()?->set(self::SELECTION_KEY, $clean);
    }

    private function session(): ?\Symfony\Component\HttpFoundation\Session\SessionInterface
    {
        $request = $this->requestStack->getMainRequest();
        if (!$request || !$request->hasSession()) {
            return null;
        }
        return $request->getSession();
    }
}
