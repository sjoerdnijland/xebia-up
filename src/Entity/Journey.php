<?php

namespace App\Entity;

use App\Repository\JourneyRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JourneyRepository::class)]
#[ORM\Table(name: 'journey')]
#[ORM\Index(name: 'IDX_JOURNEY_CLIENT', columns: ['client_id'])]
class Journey
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private string $name;

    #[ORM\Column(length: 120, options: ['default' => ''])]
    private string $audience = '';

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'journeys')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Client $client;

    /** @var string[] */
    #[ORM\Column(type: 'json', options: ['default' => '[]'])]
    private array $moduleSlugs = [];

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getAudience(): string { return $this->audience; }
    public function setAudience(string $audience): static { $this->audience = $audience; return $this; }
    public function getClient(): Client { return $this->client; }
    public function setClient(Client $client): static { $this->client = $client; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): static { $this->position = $position; return $this; }

    /** @return string[] */
    public function getModuleSlugs(): array { return $this->moduleSlugs; }

    /** @param string[] $slugs */
    public function setModuleSlugs(array $slugs): static
    {
        $this->moduleSlugs = array_values(array_unique(array_filter($slugs, 'is_string')));
        return $this;
    }

    public function containsSlug(string $slug): bool
    {
        return in_array($slug, $this->moduleSlugs, true);
    }

    public function addSlug(string $slug): static
    {
        if ($slug !== '' && !$this->containsSlug($slug)) {
            $this->moduleSlugs[] = $slug;
        }
        return $this;
    }

    public function removeSlug(string $slug): static
    {
        $this->moduleSlugs = array_values(array_filter(
            $this->moduleSlugs,
            static fn (string $s): bool => $s !== $slug,
        ));
        return $this;
    }

    /**
     * Forgiving reorder: ids not in the current list are dropped; any current slugs missing
     * from $slugs are appended at the end so a stale client doesn't accidentally lose modules.
     *
     * @param string[] $slugs
     */
    public function reorderSlugs(array $slugs): static
    {
        $clean = [];
        foreach ($slugs as $slug) {
            if (is_string($slug) && in_array($slug, $this->moduleSlugs, true) && !in_array($slug, $clean, true)) {
                $clean[] = $slug;
            }
        }
        foreach ($this->moduleSlugs as $slug) {
            if (!in_array($slug, $clean, true)) {
                $clean[] = $slug;
            }
        }
        $this->moduleSlugs = $clean;
        return $this;
    }

    public function count(): int
    {
        return count($this->moduleSlugs);
    }
}
