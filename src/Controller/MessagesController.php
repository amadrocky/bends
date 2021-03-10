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
use App\Entity\Message;
use App\Entity\Offers;
use App\Service\MailerService;

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

        $datas = $discussionsRepository->findByUser($this->getUser());

        $discussions = $paginator->paginate(
            $datas, //on passe les données
            $request->query->getInt('page', 1), //numéro de la page en cours, 1 par défaut
            10// nombre d'éléments
        );

        return $this->render('messages/index.html.twig', [
            'discussions' => $discussions,
            'user' => $this->getUser(),
            'messages' => $request->getSession()->get('messages'),
            'today' => new \DateTime(),
            'yesterday' => (new \DateTime())->modify('-1 day')
        ]);
    }

    /**
     * @Route("/new/{id}", name="new", requirements={"id":"\d+"}, methods={"POST"})
     *
     * @param Request $request
     * @param Offers $offer
     * @param DiscussionsRepository $discussionsRepository
     * @param MailerService $mailer
     * @return Response
     */
    public function new(Request $request, Offers $offer, DiscussionsRepository $discussionsRepository, MailerService $mailer): Response
    {
        $discussion = new Discussions();
        $message = new Message();
        $now = new \DateTime();
        $entityManager = $this->getDoctrine()->getManager();

        /** Cas où une discussion existe déja entre les utilisateurs sur l'offre. */
        $existingDiscussion = $discussionsRepository->findByUserAndOffer($this->getUser(), $offer);
        if ($existingDiscussion === null) {
            $discussion->setCreatedBy($this->getUser());
            $discussion->setCreatedAt($now);
            $discussion->setModifiedAt($now);
            $discussion->setWorkflowState('created');
            $discussion->setOffer($offer);
            $discussion->setIsSignaled(false);
            $discussion->setIsDeletedCreator(false);
            $discussion->setIsDeletedUser(false);
            $entityManager->persist($discussion);
            $entityManager->flush();
        } else {
            $discussion = $existingDiscussion;
            $discussion->setModifiedAt($now);
            $discussion->setIsDeletedCreator(false);
            $discussion->setIsDeletedUser(false);
        }

        $message->setText($request->request->get('message'));
        $message->setCreatedAt($now);
        $message->setCreatedBy($this->getUser());
        $message->setWorkflowState('created');
        $message->setDiscussion($discussion);
        $entityManager->persist($message);
        $entityManager->flush();

        $mailer->sendEmail(
            $offer->getCreatedBy()->getFirstname(), 
            $offer->getCreatedBy()->getEmail(), 
            'Nouveau message de ' . $this->getUser()->getPseudonym(),
            'emails/newMessage.html.twig'
        );

        return $this->json(['offer' => $offer->getId()]);
    }

    /**
     * @Route("/discussion/{id}", name="discussion", requirements={"id":"\d+"})
     *
     * @param Request $request
     * @param Discussions $discussion
     * @return Response
     */
    public function show(Request $request, Discussions $discussion, MailerService $mailer): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        if ($discussion->getCreatedBy() != $this->getUser() && $discussion->getOffer()->getCreatedBy() != $this->getUser()) {
            return $this->redirectToRoute('home');
        }

        $discussionMessages = $discussion->getMessages();
        
        /** Marque les messages lus en accédant à la discussion. */
        foreach ($discussionMessages as $message) {
            if ($message->getCreatedBy() !== $this->getUser() && $message->getWorkflowstate() === "created") {
                $entityManager = $this->getDoctrine()->getManager();
                $message->setWorkflowstate("read");
                $entityManager->persist($message);
                $entityManager->flush();
            }
        }
        
        return $this->render('messages/show.html.twig', [
            'discussion' => $discussion,
            'user' => $this->getUser(),
            'messages' => $request->getSession()->get('messages'),
            'today' => new \DateTime(),
            'yesterday' => (new \DateTime())->modify('-1 day'),
        ]);
    }

    /**
     * @Route("/discussion/{id}/new", name="discussion_message", requirements={"id":"\d+"},  methods={"POST"})
     *
     * @param Request $request
     * @param Discussions $discussion
     * @param MailerService $mailer
     * @return Response
     */
    public function newMessage(Request $request, Discussions $discussion, MailerService $mailer): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $message = new Message();
        $fileName = $_FILES['fname']['name'];

        if ($fileName !== "") {
            $uploadDir = $_SERVER['PWD'] . '/assets/static/images/messages/';
            $extention = strrchr($fileName, ".");
            $fileName = 'message_image_' . uniqid() . $extention;
            $uploadFile = $uploadDir . basename($fileName);
            move_uploaded_file($_FILES['fname']['tmp_name'], $uploadFile);
            $files[] = basename($uploadFile);

            $message->setfile($files);
        }

        if ($_POST['message'] !== '') {
            $message->setText($_POST['message']);
        }
        
        $message->setCreatedAt(new \DateTime());
        $message->setCreatedBy($this->getUser());
        $message->setDiscussion($discussion);
        $message->setWorkflowState('created');
        $discussion->setModifiedAt(new \Datetime());
        $discussion->setIsDeletedCreator(false);
        $discussion->setIsDeletedUser(false);
        $entityManager->persist($message);
        $entityManager->flush();

        $mailer->sendEmail(
            $discussion->getCreatedBy() === $this->getUser() ? $discussion->getOffer()->getCreatedBy()->getFirstname() : $discussion->getCreatedBy()->getFirstname(), 
            $discussion->getCreatedBy() === $this->getUser() ? $discussion->getOffer()->getCreatedBy()->getEmail() : $discussion->getCreatedBy()->getEmail(), 
            'Nouveau message de '. $this->getUser()->getPseudonym(),
            'emails/newMessage.html.twig'
        );

        return $this->json(['message' => $message->getId()]);
    }

    /**
     * @Route("/discussion/deleteSelection", name="deleteSelection")
     *
     * @param DiscussionsRepository $discussionsRepository
     * @return void
     */
    public function deleteSelection(DiscussionsRepository $discussionsRepository)
    {
        $entityManager = $this->getDoctrine()->getManager();

        if (empty($_POST['discussions'])) {
            $this->addFlash('error', 'Aucune conversation séléctionnée');

            $this->redirectToRoute('messages_index');
        } else {
            foreach ($_POST['discussions'] as $discussionId) {
                $discussion = $discussionsRepository->find($discussionId);
                if ($discussion->getCreatedBy() === $this->getUser()) {
                    $discussion->setIsDeletedCreator(true);
                } else {
                    $discussion->setIsDeletedUser(true);
                }
                
                $entityManager->persist($discussion);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Conversation(s) supprimée(s)');
        }

        return $this->redirectToRoute('messages_index');
    }
}
