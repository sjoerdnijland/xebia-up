<?php

namespace App\Entity;

use App\Repository\ModuleCapabilityRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ModuleCapabilityRepository::class)]
#[ORM\Table(name: 'module_capability')]
#[ORM\UniqueConstraint(name: 'uniq_module_category', columns: ['module_id', 'category_id'])]
class ModuleCapability
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Module::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Module $module;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Category $category;

    #[ORM\Column(length: 80)]
    private string $capabilityKey;

    public function getId(): ?int { return $this->id; }
    public function getModule(): Module { return $this->module; }
    public function setModule(Module $m): static { $this->module = $m; return $this; }
    public function getCategory(): Category { return $this->category; }
    public function setCategory(Category $c): static { $this->category = $c; return $this; }
    public function getCapabilityKey(): string { return $this->capabilityKey; }
    public function setCapabilityKey(string $k): static { $this->capabilityKey = $k; return $this; }
}
