<?php

namespace App\Entity;

use App\Enum\BookingStatus;
use App\Repository\BookingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[ORM\Table(name: 'booking')]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Session::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    private Session $session;

    #[ORM\Column(length: 200)]
    private string $guestName;

    #[ORM\Column(length: 200)]
    private string $guestEmail;

    #[ORM\Column(length: 50)]
    private string $guestPhone;

    #[ORM\Column(type: 'string', enumType: BookingStatus::class)]
    private BookingStatus $status = BookingStatus::Booked;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->status = BookingStatus::Booked;
    }

    public function getId(): ?int { return $this->id; }
    public function getSession(): Session { return $this->session; }
    public function setSession(Session $session): static { $this->session = $session; return $this; }
    public function getGuestName(): string { return $this->guestName; }
    public function setGuestName(string $guestName): static { $this->guestName = $guestName; return $this; }
    public function getGuestEmail(): string { return $this->guestEmail; }
    public function setGuestEmail(string $guestEmail): static { $this->guestEmail = $guestEmail; return $this; }
    public function getGuestPhone(): string { return $this->guestPhone; }
    public function setGuestPhone(string $guestPhone): static { $this->guestPhone = $guestPhone; return $this; }
    public function getStatus(): BookingStatus { return $this->status; }
    public function setStatus(BookingStatus $status): static { $this->status = $status; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }
}
