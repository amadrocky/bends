<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\MessageRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="home")
     *
     * @param Request $request
     * @param MessageRepository $message
     * @return Response
     */
    public function index(Request $request, MessageRepository $message): Response
    {
        $messages = 0;
        // if ($this->getUser()) {
        //     $messages = count($message->findUnreads($this->getUser()));
        // }

        $request->getSession()->set('messages', $messages);

        return $this->render('home/index.html.twig', [
            'user' => $this->getUser(),
            'messages' => $messages
        ]);
    }
}
