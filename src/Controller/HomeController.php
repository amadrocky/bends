<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\MessageRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Service\MailerService;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="land", methods={"GET","POST"})
     *
     * @param Request $request
     * @param MailerService $mailer
     * @return Response
     */
    public function landing(Request $request, MailerService $mailer): Response
    {
        if ($request->IsMethod('POST')) {
            $mailer->sendInBlueEmail(
                'amadou.kane.dev@gmail.com',
                7,
                [
                    'PRENOM' => $_POST['name'],
                    'EMAIL' => $_POST['email'],
                    'MESSAGE' => $_POST['message']
                ]
            );
        }

        return $this->render('home/landing.html.twig');
    }

    /**
     * @Route("/app", name="home")
     *
     * @param Request $request
     * @param MessageRepository $message
     * @return Response
     */
    public function index(Request $request, MessageRepository $message): Response
    {
        $messages = 0;

        if ($this->getUser()) {
            $messages = count($message->findUnreads($this->getUser()));
        }

        $request->getSession()->set('messages', $messages);

        return $this->render('home/index.html.twig', [
            'user' => $this->getUser(),
            'messages' => $messages
        ]);
    }
}
