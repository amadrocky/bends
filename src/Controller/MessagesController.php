<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\MessageRepository;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @Route("/messages")
 */
class MessagesController extends AbstractController
{
    /**
     * @Route("/", name="messages_index", methods={"GET"})
     *
     * @param Request $request
     * @param MessageRepository $messageRepository
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function index(Request $request, MessageRepository $messageRepository, PaginatorInterface $paginator): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $newMessages = $messageRepository->findUnreads($this->getUser());

        return $this->render('messages/index.html.twig', [
            'controller_name' => 'MessagesController',
            'user' => $this->getUser(),
            'messages' => $request->getSession()->get('messages'),
            'newMessages' => $newMessages,
            'today' => new \DateTime(),
            'yesterday' => (new \DateTime())->modify('-1 day')
        ]);
    }
}
