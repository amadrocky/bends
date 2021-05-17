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
    private $message;

    public function __construct(MessageRepository $message)
    {
        $this->message = $message;
    }

    /**
     * @Route("/", name="land", methods={"GET","POST"})
     *
     * @param Request $request
     * @return Response
     */
    public function landing(Request $request): Response
    {
        $cookie = true;

        if (isset($_COOKIE['accept-cookie'])) {
            $cookie = false;
        }
        
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

        return $this->render('home/landing.html.twig', ['cookie' => $cookie]);
    }

    /**
     * @Route("/app", name="home")
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $messages = 0;

        if ($this->getUser()) {
            $messages = count($this->message->findUnreads($this->getUser()));
        }

        $request->getSession()->set('messages', $messages);

        return $this->render('home/index.html.twig', [
            'user' => $this->getUser(),
            'messages' => $messages
        ]);
    }

    /**
     * @Route("/accept-cookie", name="accept_cookie", methods={"POST"})
     *
     * @return Response
     */
    public function setCookie(): Response
    {
        \setcookie('accept-cookie', 'true', time() + 365*24*3600); // 1 year

        return $this->json(['accept-cookie' => 'created']);
    }
}
