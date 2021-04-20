<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class MailerService 
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     *
     * @param string $name
     * @param string $email
     * @param string $subject
     * @param string $template
     * @param string $token
     * @return void
     */
    public function sendEmail(string $name, string $email, string $subject, string $template, string $token = null, $addedVar = null)
    {
        $email = (new TemplatedEmail())
            ->from('support@bends.fr')
            ->to(new Address($email))
            ->subject($subject)

            // path of the Twig template to render
            ->htmlTemplate($template)

            // pass variables (name => value) to the template
            ->context([
                'userFirstname' => $name,
                'token' => $token,
                'addedVar' => $addedVar
            ])
        ;

        $this->mailer->send($email);
    }

    /**
     *
     * @param string $email
     * @param string $subject
     * @param string $message
     * @param string $template
     * @return void
     */
    public function sendAdminEmail(string $email, string $subject, string $message, string $template)
    {
        $email = (new TemplatedEmail())
            ->from('support@bends.fr')
            ->to(new Address($email))
            ->subject($subject)
            ->htmlTemplate($template)
            ->context([
                'message' => $message
            ])
            // get the image contents from an existing file
            // ->embedFromPath('/path/to/images/signature.gif', 'footer-signature')
        ;

        $this->mailer->send($email);
    }
}