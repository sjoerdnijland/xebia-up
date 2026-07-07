<?php

namespace App\Entity;

use App\Repository\ModuleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ModuleRepository::class)]
#[ORM\Table(name: 'module')]
class Module
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    private string $title;

    #[ORM\Column(length: 200, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\ManyToOne(targetEntity: Level::class, inversedBy: 'modules')]
    #[ORM\JoinColumn(nullable: false)]
    private Level $level;

    #[ORM\ManyToOne(targetEntity: ModuleType::class, inversedBy: 'modules')]
    #[ORM\JoinColumn(nullable: true)]
    private ?ModuleType $type = null;

    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'modules')]
    #[ORM\JoinTable(name: 'module_category')]
    private Collection $categories;

    #[ORM\ManyToMany(targetEntity: Role::class, inversedBy: 'modules')]
    #[ORM\JoinTable(name: 'module_role')]
    private Collection $roles;

    #[ORM\OneToMany(targetEntity: ModuleObjective::class, mappedBy: 'module', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $objectives;

    #[ORM\OneToMany(targetEntity: Session::class, mappedBy: 'module', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['startsAt' => 'ASC'])]
    private Collection $sessions;

    #[ORM\ManyToMany(targetEntity: Skill::class, inversedBy: 'modules')]
    #[ORM\JoinTable(name: 'module_skill')]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $skills;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column]
    private int $durationHours = 4;

    #[ORM\Column]
    private bool $isActive = true;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->roles = new ArrayCollection();
        $this->objectives = new ArrayCollection();
        $this->sessions = new ArrayCollection();
        $this->skills = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }
    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): static { $this->description = $description; return $this; }
    public function getLevel(): Level { return $this->level; }
    public function setLevel(Level $level): static { $this->level = $level; return $this; }
    public function getType(): ?ModuleType { return $this->type; }
    public function setType(?ModuleType $type): static { $this->type = $type; return $this; }
    public function getCategories(): Collection { return $this->categories; }
    public function addCategory(Category $category): static { if (!$this->categories->contains($category)) { $this->categories->add($category); } return $this; }
    public function getRoles(): Collection { return $this->roles; }
    public function addRole(Role $role): static { if (!$this->roles->contains($role)) { $this->roles->add($role); } return $this; }
    public function getObjectives(): Collection { return $this->objectives; }
    public function addObjective(ModuleObjective $obj): static { if (!$this->objectives->contains($obj)) { $this->objectives->add($obj); $obj->setModule($this); } return $this; }
    public function getSessions(): Collection { return $this->sessions; }
    public function addSession(Session $session): static { if (!$this->sessions->contains($session)) { $this->sessions->add($session); $session->setModule($this); } return $this; }
    public function getSkills(): Collection { return $this->skills; }
    public function addSkill(Skill $skill): static { if (!$this->skills->contains($skill)) { $this->skills->add($skill); } return $this; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): static { $this->position = $position; return $this; }
    public function getDurationHours(): int { return $this->durationHours; }
    public function setDurationHours(int $hours): static { $this->durationHours = $hours; return $this; }
    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }

    public function getFormattedDuration(): string
    {
        $h = $this->durationHours;
        if ($h % 8 === 0) {
            $days = $h / 8;
            return $days === 1 ? '1 day' : "{$days} days";
        }
        return "{$h} hours";
    }

    public function isForAllRoles(int $totalRoles): bool
    {
        return $this->roles->count() === $totalRoles;
    }

    public function getNextSession(): ?Session
    {
        $now = new \DateTimeImmutable();
        foreach ($this->sessions as $session) {
            if ($session->getStartsAt() > $now && $session->getSeatsLeft() > 0) {
                return $session;
            }
        }
        foreach ($this->sessions as $session) {
            if ($session->getStartsAt() > $now) {
                return $session;
            }
        }
        return $this->sessions->first() ?: null;
    }
}
