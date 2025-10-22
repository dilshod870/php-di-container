<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Booking;
use App\Services\Contracts\EmailService;
use App\Services\Contracts\GuestAddressService;
use App\Support\LoggerInterface;

class BookingService
{
    public function __construct(
        private readonly GuestAddressService $addressService,
        private readonly EmailService $emailService,
        private readonly LoggerInterface $logger,
    ) {}

    public function sendConfirmationEmail(Booking $booking): void
    {
        $guest = $booking->getGuest();

        $addressInfo = $this->addressService->getGuestAddressInfo($guest);

        $subject = "Подтверждение бронирования #{$booking->getId()}";
        $message = "Здравствуйте, {$guest->getFullName()}!\n\n" .
            "Ваше бронирование подтверждено.\n" .
            "Город: {$addressInfo->city}\n" .
            "Страна: {$addressInfo->country} ({$addressInfo->countryCode})\n" .
            "Адрес: {$addressInfo->street}, {$addressInfo->zipCode}\n";

        $this->emailService->send($guest->getEmail(), $subject, $message);
        $this->logger->debug("Confirmation email sent to {$guest->getEmail()} for booking {$booking->getId()}");
    }
}
