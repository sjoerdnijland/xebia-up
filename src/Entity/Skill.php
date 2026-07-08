<?php

namespace App\Entity;

use App\Repository\SkillRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SkillRepository::class)]
#[ORM\Table(name: 'skill')]
class Skill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private string $slug;

    #[ORM\Column(length: 150)]
    private string $name;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Category $category;

    #[ORM\Column(length: 60)]
    private string $capabilityKey;

    #[ORM\Column(length: 100)]
    private string $domainName;

    #[ORM\Column(length: 60)]
    private string $domainSlug;

    #[ORM\Column(length: 40)]
    private string $ringSlug;

    #[ORM\Column(length: 100)]
    private string $ringName;

    #[ORM\ManyToOne(targetEntity: Ring::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Ring $ring = null;

    #[ORM\Column(length: 16)]
    private string $viewScope = 'common';

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column(type: 'json')]
    private array $descriptions = [];

    #[ORM\ManyToMany(targetEntity: Module::class, mappedBy: 'skills')]
    private Collection $modules;

    public function __construct()
    {
        $this->modules = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getCategory(): Category { return $this->category; }
    public function setCategory(Category $category): static { $this->category = $category; return $this; }
    public function getCapabilityKey(): string { return $this->capabilityKey; }
    public function setCapabilityKey(string $key): static { $this->capabilityKey = $key; return $this; }
    public function getDomainName(): string { return $this->domainName; }
    public function setDomainName(string $n): static { $this->domainName = $n; return $this; }
    public function getDomainSlug(): string { return $this->domainSlug; }
    public function setDomainSlug(string $s): static { $this->domainSlug = $s; return $this; }
    public function getRingSlug(): string { return $this->ringSlug; }
    public function setRingSlug(string $s): static { $this->ringSlug = $s; return $this; }
    public function getRingName(): string { return $this->ringName; }
    public function setRingName(string $n): static { $this->ringName = $n; return $this; }
    public function getRing(): ?Ring { return $this->ring; }
    public function setRing(?Ring $r): static { $this->ring = $r; return $this; }
    public function getViewScope(): string { return $this->viewScope; }
    public function setViewScope(string $v): static { $this->viewScope = $v; return $this; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(int $p): static { $this->position = $p; return $this; }
    public function getDescriptions(): array { return $this->descriptions; }
    public function setDescriptions(array $d): static { $this->descriptions = $d; return $this; }

    public function descriptionFor(string $levelSlug): ?string
    {
        return $this->descriptions[$levelSlug] ?? null;
    }

    public function getModules(): Collection
    {
        return $this->modules;
    }
}
