<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\MessageRepository;
use App\Repository\DiscussionsRepository;
use Knp\Component\Pager\PaginatorInterface;
use App\Entity\Discussions;

/**
 * @Route("/messages", name="messages_")
 */
class MessagesController extends AbstractController
{
    /**
     * @Route("/", name="index", methods={"GET"})
     *
     * @param Request $request
     * @param MessageRepository $messageRepository
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function index(Request $request, DiscussionsRepository $discussionsRepository, PaginatorInterface $paginator): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $discussions = $discussionsRepository->findByUser($this->getUser());

        return $this->render('messages/index.html.twig', [
            'discussions' => $discussions,
            'controller_name' => 'MessagesController',
            'user' => $this->getUser(),
            'messages' => $request->getSession()->get('messages'),
            'today' => new \DateTime(),
            'yesterday' => (new \DateTime())->modify('-1 day')
        ]);
    }

    /**
     * @Route("/discussion/{id}", name="discussion", requirements={"id":"\d+"})
     *
     * @param Request $request
     * @param Discussions $discussion
     * @return Response
     */
    public function show(Request $request, Discussions $discussion): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        if ($discussion->getCreatedBy() != $this->getUser() && $discussion->getUser() != $this->getUser()) {

            $this->addFlash('error', 'Accès refusé');
            return $this->redirectToRoute('home');
        }
        
        return $this->render('messages/show.html.twig', [
            'discussion' => $discussion,
            'user' => $this->getUser(),
            'messages' => $request->getSession()->get('messages'),
            'today' => new \DateTime(),
            'yesterday' => (new \DateTime())->modify('-1 day'),
        ]);
    }
}
