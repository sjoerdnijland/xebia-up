<?php

namespace App\Service;

use App\Entity\Client;
use App\Entity\Journey;
use App\Repository\ClientRepository;
use App\Repository\JourneyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

class JourneyCollection
{
    private const KEY_MODE   = 'in_company_mode';
    private const KEY_ACTIVE = 'active_journey_id';

    /** Legacy session keys that may still hold stale data from before the DB rollout */
    private const STALE_KEYS = [
        'journeys',
        'journey_order',
        'in_company_client_name',
        'in_company_selection',
        'in_company_role_name',
    ];

    private bool $sessionScrubbed = false;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ClientRepository $clientRepo,
        private readonly JourneyRepository $journeyRepo,
        private readonly EntityManagerInterface $em,
        private readonly bool $registrationOpen = true,
    ) {
    }

    public function isRegistrationOpen(): bool
    {
        return $this->registrationOpen;
    }

    /* --- mode --- */

    public function isOn(): bool
    {
        // When registration is closed, in-company mode is always on
        if (!$this->registrationOpen) {
            return true;
        }
        return (bool) ($this->session()?->get(self::KEY_MODE, false));
    }

    public function toggleMode(): bool
    {
        $s = $this->session();
        if (!$s) {
            return false;
        }
        $next = !$s->get(self::KEY_MODE, false);
        $s->set(self::KEY_MODE, $next);
        return $next;
    }

    /* --- clients --- */

    /** @return Client[] */
    public function allClients(): array
    {
        return $this->clientRepo->findAllOrdered();
    }

    public function getClient(int $id): ?Client
    {
        return $this->clientRepo->find($id);
    }

    public function createClient(string $name): Client
    {
        $name = mb_substr(trim($name), 0, 120);
        if ($name === '') {
            throw new \InvalidArgumentException('Client name is required.');
        }

        $existing = $this->clientRepo->findByName($name);
        if ($existing) {
            return $existing;
        }

        $client = (new Client())
            ->setName($name)
            ->setSlug($this->uniqueClientSlug($name));
        $this->em->persist($client);
        $this->em->flush();
        return $client;
    }

    public function renameClient(int $id, string $name): ?Client
    {
        $client = $this->clientRepo->find($id);
        if (!$client) {
            return null;
        }
        $name = mb_substr(trim($name), 0, 120);
        if ($name === '' || $name === $client->getName()) {
            return $client;
        }
        $other = $this->clientRepo->findByName($name);
        if ($other && $other->getId() !== $client->getId()) {
            // Name collision — leave unchanged.
            return $client;
        }
        $client->setName($name);
        $client->setSlug($this->uniqueClientSlug($name, $client->getId()));
        $this->em->flush();
        return $client;
    }

    public function removeClient(int $id): void
    {
        $client = $this->clientRepo->find($id);
        if (!$client) {
            return;
        }
        $this->em->remove($client);
        $this->em->flush();

        $s = $this->session();
        if ($s && (int) $s->get(self::KEY_ACTIVE, 0) === $id) {
            $s->remove(self::KEY_ACTIVE);
        }
    }

    /* --- journey list --- */

    /** @return Journey[] */
    public function ordered(): array
    {
        return $this->journeyRepo->findAllOrdered();
    }

    public function get(int|string $id): ?Journey
    {
        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }
        return $this->journeyRepo->find($id);
    }

    public function count(): int
    {
        return $this->journeyRepo->count([]);
    }

    public function totalModules(): int
    {
        $n = 0;
        foreach ($this->ordered() as $j) {
            $n += $j->count();
        }
        return $n;
    }

    public function create(string $name, string $audience, ?int $clientId, ?string $clientName = null): Journey
    {
        $name = mb_substr(trim($name), 0, 120);
        if ($name === '') {
            $name = 'Untitled journey';
        }
        $audience = mb_substr(trim($audience), 0, 120);

        $client = null;
        if ($clientId !== null && $clientId > 0) {
            $client = $this->clientRepo->find($clientId);
        }
        if (!$client && $clientName !== null && trim($clientName) !== '') {
            $client = $this->createClient($clientName);
        }
        if (!$client) {
            throw new \InvalidArgumentException('A client is required to create a journey.');
        }

        $journey = (new Journey())
            ->setName($name)
            ->setAudience($audience)
            ->setClient($client)
            ->setPosition($this->nextPositionForClient($client));
        $this->em->persist($journey);
        $this->em->flush();

        $this->setActive($journey->getId());
        return $journey;
    }

    public function rename(int|string $id, ?string $name, ?string $audience, ?int $clientId = null): void
    {
        $journey = $this->get($id);
        if (!$journey) {
            return;
        }
        if ($name !== null) {
            $name = mb_substr(trim($name), 0, 120);
            if ($name !== '') {
                $journey->setName($name);
            }
        }
        if ($audience !== null) {
            $journey->setAudience(mb_substr(trim($audience), 0, 120));
        }
        if ($clientId !== null && $clientId > 0) {
            $client = $this->clientRepo->find($clientId);
            if ($client) {
                $journey->setClient($client);
            }
        }
        $this->em->flush();
    }

    public function remove(int|string $id): void
    {
        $journey = $this->get($id);
        if (!$journey) {
            return;
        }
        $this->em->remove($journey);
        $this->em->flush();

        $s = $this->session();
        if ($s && (int) $s->get(self::KEY_ACTIVE, 0) === (int) $id) {
            $s->remove(self::KEY_ACTIVE);
        }
    }

    /* --- active journey --- */

    public function getActive(): ?Journey
    {
        $s = $this->session();
        if (!$s) {
            return null;
        }
        $id = (int) ($s->get(self::KEY_ACTIVE) ?? 0);
        if ($id > 0) {
            $j = $this->get($id);
            if ($j) {
                return $j;
            }
        }
        $first = $this->ordered()[0] ?? null;
        if ($first) {
            $s->set(self::KEY_ACTIVE, $first->getId());
        }
        return $first;
    }

    public function setActive(int|string $id): void
    {
        $j = $this->get($id);
        if ($j) {
            $this->session()?->set(self::KEY_ACTIVE, $j->getId());
        }
    }

    /* --- module ops --- */

    public function addSlugTo(int|string $id, string $slug): void
    {
        $j = $this->get($id);
        if (!$j || $slug === '') {
            return;
        }
        $j->addSlug($slug);
        $this->em->flush();
    }

    public function removeSlugFrom(int|string $id, string $slug): void
    {
        $j = $this->get($id);
        if (!$j) {
            return;
        }
        $j->removeSlug($slug);
        $this->em->flush();
    }

    public function reorderWithin(int|string $id, array $slugs): void
    {
        $j = $this->get($id);
        if (!$j) {
            return;
        }
        $j->reorderSlugs($slugs);
        $this->em->flush();
    }

    /** @return Journey[] */
    public function journeysContainingSlug(string $slug): array
    {
        return $this->journeyRepo->findContainingSlug($slug);
    }

    /**
     * Journeys grouped by their client.
     * @return array<int, array{client: Client, journeys: Journey[]}>
     */
    public function groupedByClient(): array
    {
        $out = [];
        foreach ($this->allClients() as $client) {
            $out[] = [
                'client' => $client,
                'journeys' => $this->journeyRepo->findByClient($client),
            ];
        }
        return $out;
    }

    /* --- helpers --- */

    private function nextPositionForClient(Client $client): int
    {
        $max = 0;
        foreach ($this->journeyRepo->findByClient($client) as $j) {
            $max = max($max, $j->getPosition());
        }
        return $max + 1;
    }

    private function uniqueClientSlug(string $name, ?int $ignoreId = null): string
    {
        $slugger = new AsciiSlugger();
        $base = $slugger->slug($name)->lower()->toString();
        if ($base === '') {
            $base = 'client';
        }
        $candidate = $base;
        $n = 2;
        while (true) {
            $existing = $this->clientRepo->findBySlug($candidate);
            if (!$existing || ($ignoreId !== null && $existing->getId() === $ignoreId)) {
                return $candidate;
            }
            $candidate = $base . '-' . $n;
            $n++;
        }
    }

    private function session(): ?SessionInterface
    {
        $request = $this->requestStack->getMainRequest();
        if (!$request || !$request->hasSession()) {
            return null;
        }
        $s = $request->getSession();
        $this->scrubLegacy($s);
        return $s;
    }

    private function scrubLegacy(SessionInterface $s): void
    {
        if ($this->sessionScrubbed) {
            return;
        }
        $this->sessionScrubbed = true;
        foreach (self::STALE_KEYS as $key) {
            if ($s->has($key)) {
                $s->remove($key);
            }
        }
    }
}
