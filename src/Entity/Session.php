<?php

namespace App\Entity;

use App\Enum\BookingStatus;
use App\Enum\SessionFormat;
use App\Repository\SessionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SessionRepository::class)]
#[ORM\Table(name: 'session')]
class Session
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Module::class, inversedBy: 'sessions')]
    #[ORM\JoinColumn(nullable: false)]
    private Module $module;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $startsAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $endsAt;

    #[ORM\Column(type: 'string', enumType: SessionFormat::class)]
    private SessionFormat $format;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $location = null;

    #[ORM\Column]
    private int $capacity = 12;

    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'session', cascade: ['persist'])]
    private Collection $bookings;

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getModule(): Module { return $this->module; }
    public function setModule(Module $module): static { $this->module = $module; return $this; }
    public function getStartsAt(): \DateTimeImmutable { return $this->startsAt; }
    public function setStartsAt(\DateTimeImmutable $startsAt): static { $this->startsAt = $startsAt; return $this; }
    public function getEndsAt(): \DateTimeImmutable { return $this->endsAt; }
    public function setEndsAt(\DateTimeImmutable $endsAt): static { $this->endsAt = $endsAt; return $this; }
    public function getFormat(): SessionFormat { return $this->format; }
    public function setFormat(SessionFormat $format): static { $this->format = $format; return $this; }
    public function getLocation(): ?string { return $this->location; }
    public function setLocation(?string $location): static { $this->location = $location; return $this; }
    public function getCapacity(): int { return $this->capacity; }
    public function setCapacity(int $capacity): static { $this->capacity = $capacity; return $this; }
    public function getBookings(): Collection { return $this->bookings; }
    public function addBooking(Booking $booking): static { if (!$this->bookings->contains($booking)) { $this->bookings->add($booking); $booking->setSession($this); } return $this; }

    public function getSeatsLeft(): int
    {
        $active = $this->bookings->filter(fn(Booking $b) => $b->getStatus() === BookingStatus::Booked)->count();
        return max(0, $this->capacity - $active);
    }

    public function getActiveBookingsCount(): int
    {
        return $this->bookings->filter(fn(Booking $b) => $b->getStatus() === BookingStatus::Booked)->count();
    }

    public function getFormattedTime(): string
    {
        $start = $this->startsAt->format('H:i');
        $end = $this->endsAt->format('H:i');
        return $start . '–' . $end;
    }

    public function getLocationLabel(): string
    {
        if ($this->format === SessionFormat::Online) {
            return 'Remote';
        }
        return $this->location ?? 'TBD';
    }
}
