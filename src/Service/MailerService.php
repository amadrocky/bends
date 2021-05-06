<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use SendinBlue\Client\Configuration;
use SendinBlue\Client\Api\TransactionalEmailsApi;
use SendinBlue\Bundle\ApiBundle\SendinBlueApiBundle;
use SendinBlue\Client\Model\SendSmtpEmail;
use GuzzleHttp\Client;

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

    /**
     * Send an email via SendInBlue
     *
     * @param string $to
     * @param integer $templateId
     * @param array $params
     * @return void
     */
    public function sendInBlueEmail(string $to, int $templateId, array $params)
    {
        // Configure API key authorization: api-key
        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', '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');

        $apiInstance = new TransactionalEmailsApi(
            // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
            // This is optional, `GuzzleHttp\Client` will be used as default.
            new Client(),
            $config
        );
        $sendSmtpEmail = new SendSmtpEmail(); // \SendinBlue\Client\Model\SendSmtpEmail | Values to send a transactional email
        $sendSmtpEmail['to'] = [['email' => $to]];
        $sendSmtpEmail['templateId'] = $templateId;
        $sendSmtpEmail['params'] = $params;
        //$sendSmtpEmail['headers'] = ['X-Mailin-custom' => 'content-type:application/json|accept:application/json'];

        try {
            $result = $apiInstance->sendTransacEmail($sendSmtpEmail);
        } catch (Exception $e) {
            echo 'Exception when calling TransactionalEmailsApi->sendTransacEmail: ', $e->getMessage(), PHP_EOL;
        }
    }
}