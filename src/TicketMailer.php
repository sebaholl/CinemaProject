<?php
declare(strict_types=1);

// Builds the CONTENT of the ticket confirmation email and hands it to a Mailer.
//
// The split is deliberate and is the clearest example of separation of concerns
// in this project:
//   Ticket       - the data (built by BookingRepository from the database)
//   TicketMailer - turns that data into a subject + two message bodies
//   Mailer       - puts a message on the wire, and knows nothing about cinemas
// Swapping the wording means touching only the templates; swapping SMTP for
// something else means touching only Mailer.
final class TicketMailer
{
    private const HTML_TEMPLATE = __DIR__ . '/../templates/email/ticket.php';
    private const TEXT_TEMPLATE = __DIR__ . '/../templates/email/ticket.txt.php';

    public function __construct(private Mailer $mailer) {}

    public function sendConfirmation(Ticket $ticket): bool
    {
        $subject = sprintf(
            'Your tickets for %s - %s at %s',
            $ticket->getMovieTitle(),
            $ticket->getDateLabel(),
            $ticket->getScreeningTime()
        );

        return $this->mailer->send(
            $ticket->getCustomerEmail(),
            $ticket->getCustomerName(),
            $subject,
            $this->render(self::HTML_TEMPLATE, $ticket),
            $this->render(self::TEXT_TEMPLATE, $ticket)
        );
    }

    /**
     * Run a template file and capture what it prints, instead of sending it to
     * the browser. The template sees $ticket and nothing else.
     */
    private function render(string $templateFile, Ticket $ticket): string
    {
        if (!is_file($templateFile)) {
            throw new RuntimeException('Email template is missing: ' . $templateFile);
        }

        ob_start();
        try {
            include $templateFile;
            return (string) ob_get_clean();
        } catch (Throwable $e) {
            // Never leave a half-finished buffer behind if a template blows up.
            ob_end_clean();
            throw $e;
        }
    }
}
