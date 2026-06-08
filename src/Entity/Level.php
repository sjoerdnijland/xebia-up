<?php

namespace App\Entity;

use App\Repository\LevelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LevelRepository::class)]
#[ORM\Table(name: 'level')]
class Level
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(length: 100, unique: true)]
    private string $slug;

    #[ORM\Column]
    private int $depth;

    #[ORM\Column(length: 10)]
    private string $colorHex;

    #[ORM\Column(length: 10)]
    private string $tintHex;

    #[ORM\Column(length: 300)]
    private string $blurb;

    #[ORM\OneToMany(targetEntity: Module::class, mappedBy: 'level')]
    private Collection $modules;

    public function __construct()
    {
        $this->modules = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }
    public function getDepth(): int { return $this->depth; }
    public function setDepth(int $depth): static { $this->depth = $depth; return $this; }
    public function getColorHex(): string { return $this->colorHex; }
    public function setColorHex(string $colorHex): static { $this->colorHex = $colorHex; return $this; }
    public function getTintHex(): string { return $this->tintHex; }
    public function setTintHex(string $tintHex): static { $this->tintHex = $tintHex; return $this; }
    public function getBlurb(): string { return $this->blurb; }
    public function setBlurb(string $blurb): static { $this->blurb = $blurb; return $this; }
    public function getModules(): Collection { return $this->modules; }
}
