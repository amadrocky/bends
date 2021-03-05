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

        if (isset($_FILES['fname']['name']) || isset($_POST['message'])) {
            $files = [];

            if ($_FILES['fname']['name'] !== "" || $_POST['message'] !== "") {
                $message = new Message();
                $entityManager = $this->getDoctrine()->getManager();

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

                if ($_POST['message'] !== "") {
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

                $this->addFlash('success', 'Message envoyé');
            } else {
                $this->addFlash('error', 'Aucune donnée saisie');
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
