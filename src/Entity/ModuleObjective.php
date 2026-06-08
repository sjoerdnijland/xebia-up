<?php

namespace App\Entity;

use App\Repository\ModuleObjectiveRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ModuleObjectiveRepository::class)]
#[ORM\Table(name: 'module_objective')]
class ModuleObjective
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Module::class, inversedBy: 'objectives')]
    #[ORM\JoinColumn(nullable: false)]
    private Module $module;

    #[ORM\Column(length: 500)]
    private string $text;

    #[ORM\Column]
    private int $position = 0;

    public function getId(): ?int { return $this->id; }
    public function getModule(): Module { return $this->module; }
    public function setModule(Module $module): static { $this->module = $module; return $this; }
    public function getText(): string { return $this->text; }
    public function setText(string $text): static { $this->text = $text; return $this; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): static { $this->position = $position; return $this; }
}
