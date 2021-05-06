<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\MessageRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\ContactMessage;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="land", methods={"GET","POST"})
     *
     * @param Request $request
     * @return Response
     */
    public function landing(Request $request): Response
    {
        if ($request->IsMethod('POST')) {
            $message = new ContactMessage();
            $em = $this->getDoctrine()->getManager();

            $message->setName($_POST['name']);
            $message->setEmail($_POST['email']);
            $message->setMessage($_POST['message']);
            $message->setCreatedAt(new \DateTime());
            $message->setWorkflowState('active');
            $em->persist($message);
            $em->flush();
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
