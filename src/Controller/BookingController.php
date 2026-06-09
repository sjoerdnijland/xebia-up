<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Enum\BookingStatus;
use App\Form\BookingType;
use App\Repository\ModuleRepository;
use App\Repository\SessionRepository;
use App\Service\JourneyBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BookingController extends AbstractController
{
    #[Route('/modules/{slug}/book', name: 'module_book', methods: ['GET', 'POST'])]
    public function book(
        string $slug,
        Request $request,
        ModuleRepository $moduleRepo,
        SessionRepository $sessionRepo,
        EntityManagerInterface $em,
        JourneyBuilder $journey,
    ): Response {
        if ($journey->isOn()) {
            return $this->redirectToRoute('module_show', ['slug' => $slug]);
        }

        $module = $moduleRepo->findOneBy(['slug' => $slug]);
        if (!$module) {
            throw $this->createNotFoundException('Module not found');
        }

        $sessionId = $request->query->getInt('session') ?: $request->request->getInt('session');
        $session = $sessionId ? $sessionRepo->find($sessionId) : null;

        // Default to first available session
        if (!$session) {
            $session = $module->getNextSession();
        }
        if (!$session) {
            throw $this->createNotFoundException('No sessions available');
        }

        $form = $this->createForm(BookingType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $booking = new Booking();
            $booking->setSession($session);
            $booking->setGuestName($data['guestName']);
            $booking->setGuestEmail($data['guestEmail']);
            $booking->setGuestPhone($data['guestPhone']);

            $seatsLeft = $session->getSeatsLeft();
            $booking->setStatus($seatsLeft > 0 ? BookingStatus::Booked : BookingStatus::Waitlisted);

            $em->persist($booking);
            $em->flush();

            return $this->redirectToRoute('booking_confirm', ['id' => $booking->getId()]);
        }

        return $this->render('booking/new.html.twig', [
            'module' => $module,
            'session' => $session,
            'form' => $form,
            'seatsLeft' => $session->getSeatsLeft(),
        ]);
    }

    #[Route('/bookings/{id}/confirm', name: 'booking_confirm', methods: ['GET'])]
    public function confirm(int $id, \App\Repository\BookingRepository $bookingRepo): Response
    {
        $booking = $bookingRepo->find($id);
        if (!$booking) {
            throw $this->createNotFoundException('Booking not found');
        }

        return $this->render('booking/confirm.html.twig', [
            'booking' => $booking,
            'module' => $booking->getSession()->getModule(),
            'session' => $booking->getSession(),
        ]);
    }
}
