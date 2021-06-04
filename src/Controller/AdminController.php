<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Offers;
use App\Entity\User;
use App\Entity\Associations;
use App\Repository\OffersRepository;
use App\Repository\UserRepository;
use App\Repository\AssociationsRepository;
use App\Service\MailerService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Articles;
use App\Repository\ArticlesRepository;
use App\Form\ArticleType;
use App\Repository\SignaledOffersRepository;
use App\Repository\SignaledDiscussionsRepository;
use App\Entity\SignaledOffers;
use App\Entity\SignaledDiscussions;
use App\Service\ReportsService;
use App\Entity\ContactMessage;
use App\Repository\ContactMessageRepository;

/**
 * * @Route("/admin", name="admin_")
 */
class AdminController extends AbstractController
{
    private $mailer;

    private $messages;

    private $paginator;

    public function __construct(
        OffersRepository $offersRepository, 
        UserRepository $userRepository, 
        AssociationsRepository $associationsRepository, 
        MailerService $mailer, 
        ArticlesRepository $articlesRepository,
        SignaledOffersRepository $signaledOffersRepository,
        SignaledDiscussionsRepository $signaledDiscussionsRepository,
        ReportsService $reportsService,
        ContactMessageRepository $messages,
        PaginatorInterface $paginator
    )
    {
        $this->offersRepository = $offersRepository;
        $this->userRepository = $userRepository;
        $this->associationsRepository = $associationsRepository;
        $this->mailer = $mailer;
        $this->articlesRepository = $articlesRepository;
        $this->signaledOffersRepository = $signaledOffersRepository;
        $this->signaledDiscussionsRepository = $signaledDiscussionsRepository;
        $this->reportsService = $reportsService;
        $this->messages = $messages;
        $this->paginator = $paginator;
    }

    /**
     * @Route("/", name="home")
     *
     * @return Response
     */
    public function index(): Response
    {
        $offersDatas = $this->getDatas($this->offersRepository);
        $usersDatas = $this->getDatas($this->userRepository);
        $associationsDatas = $this->getDatas($this->associationsRepository);

        $categoriesDatas = $this->offersRepository->getByCategories();
        $total = 0;
        foreach($categoriesDatas as $value){
            $total += $value['nbOffers'];
        }

        return $this->render('admin/index.html.twig', [
            'user' => $this->getUser(),
            'countOffers' => count($this->offersRepository->findByworkflowState('active')),
            'countUsers' => count($this->userRepository->findAll()),
            'countAsso' => count($this->associationsRepository->findByworkflowState('active')),
            'countValidations' => count($this->offersRepository->findByWorkflowState('created')),
            'offersDatas' => $offersDatas,
            'usersDatas' => $usersDatas,
            'associationsDatas' => $associationsDatas,
            'categoriesDatas' => $categoriesDatas,
            'total' => $total,
            'countReports' => $this->reportsService->getCountOfReportedElements(),
            'messages' => count($this->messages->findByworkflowState('active'))
        ]);
    }

    /**
     * @Route("/offers", name="offers")
     *
     * @param Request $request
     * @return Response
     */
    public function adminOffers(Request $request): Response
    {
        $datas = $this->offersRepository->findBy(['workflowState' => 'active'], ['createdAt' => 'DESC']);

        $offers = $this->paginator->paginate($datas, $request->query->getInt('page', 1), 20);

        return $this->render('admin/offers/index.html.twig', [
            'user' => $this->getUser(),
            'offers' => $offers,
            'countValidations' => count($this->offersRepository->findByWorkflowState('created')),
            'countReports' => $this->reportsService->getCountOfReportedElements(),
            'messages' => count($this->messages->findByworkflowState('active'))
        ]);
    }

    /**
     * @Route("/offers/validations", name="offers_validations")
     *
     * @param Request $request
     * @return Response
     */
    public function adminOffersToValidate(Request $request): Response
    {
        $datas = $this->offersRepository->findByWorkflowState('created');

        $offers = $this->paginator->paginate($datas, $request->query->getInt('page', 1), 20);

        return $this->render('admin/offers/validations.html.twig', [
            'user' => $this->getUser(),
            'offers' => $offers,
            'countValidations' => count($this->offersRepository->findByWorkflowState('created')),
            'countReports' => $this->reportsService->getCountOfReportedElements(),
            'messages' => count($this->messages->findByworkflowState('active'))
        ]);
    }

    /**
     * @Route("/offers/{id}", name="offers_show", requirements={"id":"\d+"})
     *
     * @param Offers $offer
     * @return Response
     */
    public function adminOffersShow(Offers $offer): Response
    {
        return $this->render('admin/offers/show.html.twig', [
            'user' => $this->getUser(),
            'offer' => $offer,
            'offerAssociation' => $this->associationsRepository->findByOffer($offer),
            'countValidations' => count($this->offersRepository->findByWorkflowState('created')),
            'countReports' => $this->reportsService->getCountOfReportedElements(),
            'messages' => count($this->messages->findByworkflowState('active'))
        ]);
    }

    /**
     * @Route("/offers/{id}/action", name="offers_action", requirements={"id":"\d+"}, methods={"POST"})
     *
     * @param Offers $offer
     * @return Response
     */
    public function offerAction(Offers $offer): Response
    {
        $em = $this->getDoctrine()->getManager();

        $offer->setWorkflowState($_POST['action']);
        $offer->setModifiedAt(new \DateTime());
        $em->persist($offer);
        $em->flush();

        if ($_POST['action'] == 'active') {
            $this->mailer->sendInBlueEmail(
                $offer->getCreatedBy()->getEmail(),
                2,
                [
                    'PRENOM' => $offer->getCreatedBy()->getFirstname()
                ]
            );
        } else {
            $this->mailer->sendInBlueEmail(
                $offer->getCreatedBy()->getEmail(),
                5,
                [
                    'PRENOM' => $offer->getCreatedBy()->getFirstname()
                ]
            );
        }

        return $this->json(['offer' => $offer->getId()]);
    }


    /**
     * @Route("/users", name="users")
     *
     * @return Response
     */
    public function adminUsers(): Response
    {
        return $this->render('admin/users/index.html.twig', [
            'user' => $this->getUser(),
            'users' => $this->userRepository->getUsersArray(),
            'countValidations' => count($this->offersRepository->findByWorkflowState('created')),
            'countReports' => $this->reportsService->getCountOfReportedElements(),
            'messages' => count($this->messages->findByworkflowState('active'))
        ]);
    }

    /**
     * @Route("/users/{id}", name="users_show", requirements={"id":"\d+"})
     *
     * @param User $adminUser
     * @return Response
     */
    public function adminUsersShow(User $adminUser): Response
    {
        return $this->render('admin/users/show.html.twig', [
            'user' => $this->getUser(),
            'adminUser' => $adminUser,
            'userAssociation' => $this->associationsRepository->findBy(['createdBy' => $adminUser->getId(), 'workflowState' => 'active']) ? $this->associationsRepository->findBy(['createdBy' => $adminUser->getId(), 'workflowState' => 'active'])[0] : [],
            'userOffers' => $this->offersRepository->findBy(['createdBy' => $adminUser->getId(), 'workflowState' => 'active'], ['createdAt' => 'DESC']),
            'countValidations' => count($this->offersRepository->findByWorkflowState('created')),
            'countReports' => $this->reportsService->getCountOfReportedElements(),
            'messages' => count($this->messages->findByworkflowState('active'))
        ]);
    }

    /**
     * @Route("/users/{id}/action", name="users_action", requirements={"id":"\d+"}, methods={"POST"})
     *
     * @param User $user
     * @return Response
     */
    public function adminUsersAction(User $user): Response
    {
        $em = $this->getDoctrine()->getManager();

        $user->setWorkflowState($_POST['action']);
        $user->setModifiedAt(new \DateTime());
        $em->persist($user);

        $userOffers = $_POST['action'] == 'active' ? $this->offersRepository->findBy(['createdBy' => $user->getId(), 'workflowState' => 'inactive']) : $this->offersRepository->findBy(['createdBy' => $user->getId(), 'workflowState' => 'active']);
        if(count($userOffers) > 0){
            foreach($userOffers as $offer){
                $offer->setWorkFlowState($_POST['action']);
                $offer->setModifiedAt(new \DateTime());
                $em->persist($offer);
            }
        }

        $userAsso = $_POST['action'] == 'active' ? $this->associationsRepository->findBy(['createdBy' => $user->getId(), 'workflowState' => 'inactive']) : $this->associationsRepository->findBy(['createdBy' => $user->getId(), 'workflowState' => 'active']);
        if(count($userAsso) > 0){
            $userAsso[0]->setWorkFlowState($_POST['action']);
            $userAsso[0]->setModifiedAt(new \DateTime());
            $em->persist($userAsso[0]);
        }

        $em->flush();

        if ($_POST['action'] == 'active') {
            $this->mailer->sendInBlueEmail(
                $user->getEmail(),
                12,
                [
                    'PRENOM' => $user->getFirstname()
                ]
            );
        } else {
            $this->mailer->sendInBlueEmail(
                $user->getEmail(),
                6,
                [
                    'PRENOM' => $user->getFirstname()
                ]
            );
        }

        return $this->json(['user' => $user->getId()]);
    }

    /**
     * @Route("/associations", name="associations")
     *
     * @return Response
     */
    public function adminAssociations(): Response
    {
        return $this->render('admin/associations/index.html.twig', [
            'user' => $this->getUser(),
            'associations' => $this->associationsRepository->getAssociationsArray(),
            'countValidations' => count($this->offersRepository->findByWorkflowState('created')),
            'countReports' => $this->reportsService->getCountOfReportedElements(),
            'messages' => count($this->messages->findByworkflowState('active'))
        ]);
    }

    /**
     * @Route("/associations/{id}", name="associations_show", requirements={"id":"\d+"})
     *
     * @param Associations $association
     * @return Response
     */
    public function adminAssociationsShow(Associations $association): Response
    {
        return $this->render('admin/associations/show.html.twig', [
            'user' => $this->getUser(),
            'association' => $association,
            'countValidations' => count($this->offersRepository->findByWorkflowState('created')),
            'countReports' => $this->reportsService->getCountOfReportedElements(),
            'messages' => count($this->messages->findByworkflowState('active'))
        ]);
    }

    /**
     * @Route("/associations/{id}/action", name="associations_action", requirements={"id":"\d+"}, methods={"POST"})
     *
     * @param Associations $association
     * @return Response
     */
    public function adminAssociationsAction(Associations $association): Response
    {
        $em = $this->getDoctrine()->getManager();

        $association->setWorkflowState($_POST['action']);
        $association->setModifiedAt(new \DateTime());
        $em->persist($association);
        $em->flush();

        if ($_POST['action'] == 'active') {
            $this->mailer->sendInBlueEmail(
                $association->getCreatedBy()->getEmail(),
                1,
                [
                    'PRENOM' => $association->getCreatedBy()->getFirstname()
                ]
            );
        } else {
            $this->mailer->sendInBlueEmail(
                $association->getCreatedBy()->getEmail(),
                4,
                [
                    'PRENOM' => $association->getCreatedBy()->getFirstname()
                ]
            );
        }

        return $this->json(['association' => $association->getId()]);
    }

    /**
     * @Route("/articles", name="articles")
     *
     * @return Response
     */
    public function adminArticles(): Response
    {
        return $this->render('admin/articles/index.html.twig', [
            'user' => $this->getUser(),
            'articles' => $this->articlesRepository->getArticlesArray(),
            'countValidations' => count($this->offersRepository->findByWorkflowState('created')),
            'countReports' => $this->reportsService->getCountOfReportedElements(),
            'messages' => count($this->messages->findByworkflowState('active'))
        ]);
    }

    /**
     * @Route("/articles/new", name="articles_new", methods={"GET","POST"})
     *
     * @param Request $request
     * @return Response
     */
    public function adminArticlesNew(Request $request): Response
    {
        $article = new Articles();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            $fileName = $_FILES['imgArticle']['name'];
            $uploadDir = '/var/www/bends/bends/public/bends/images/actualities/';

            if ($fileName !== "") {
                $file = null;

                /* On renomme l'image */
                $extention = strrchr($fileName, ".");
                $fileName = 'article_' . uniqid() . $extention;

                $uploadFile = $uploadDir . basename($fileName);
                move_uploaded_file($_FILES['imgArticle']['tmp_name'], $uploadFile);
                $file = basename($uploadFile);

                $article->setImage($fileName);
            }

            $article->setCreatedAt(new \DateTime());
            $article->setModifiedAt(new \DateTime());
            $article->setWorkflowState('active');
            $entityManager->persist($article);
            $entityManager->flush();
            
            return $this->redirectToRoute('admin_articles');
        }

        return $this->render('admin/articles/new.html.twig', [
            'user' => $this->getUser(),
            'countValidations' => count($this->offersRepository->findByWorkflowState('created')),
            'form' => $form->createView(),
            'countReports' => $this->reportsService->getCountOfReportedElements(),
            'messages' => count($this->messages->findByworkflowState('active'))
        ]);
    }

    /**
     * @Route("/articles/{id}", name="articles_show", requirements={"id":"\d+"})
     *
     * @param Articles $article
     * @return Response
     */
    public function adminArticlesShow(Articles $article): Response
    {
        return $this->render('admin/articles/show.html.twig', [
            'user' => $this->getUser(),
            'article' => $article,
            'countValidations' => count($this->offersRepository->findByWorkflowState('created')),
            'countReports' => $this->reportsService->getCountOfReportedElements(),
            'messages' => count($this->messages->findByworkflowState('active'))
        ]);
    }

    /**
     * @Route("/articles/{id}/action", name="articles_action", requirements={"id":"\d+"}, methods={"POST"})
     *
     * @param Articles $article
     * @return Response
     */
    public function adminArticlesAction(Articles $article): Response
    {
        $em = $this->getDoctrine()->getManager();

        $article->setWorkflowState($_POST['action']);
        $article->setModifiedAt(new \DateTime());
        $em->persist($article);
        $em->flush();

        return $this->json(['article' => $article->getId()]);
    }

    /**
     * @Route("/messages", name="messages", methods={"GET","POST"})
     *
     * @param Request $request
     * @return Response
     */
    public function adminSendMessage(Request $request): Response
    {
        if ($request->IsMethod('POST')) {
            $this->mailer->sendInBlueEmail(
                $_POST['email'],
                3,
                [
                    'OBJET' => $_POST['subject'],
                    'MESSAGE' => $_POST['message']
                ]
            );
        }

        return $this->render('admin/messages.html.twig', [
            'user' => $this->getUser(),
            'countValidations' => count($this->offersRepository->findByWorkflowState('created')),
            'countReports' => $this->reportsService->getCountOfReportedElements(),
            'contactMessages' => $this->messages->findAll(),
            'messages' => count($this->messages->findByworkflowState('active'))
        ]);
    }

    /**
     * @Route("/messages/association", name="messages_association", methods={"POST"})
     *
     * @param Request $request
     * @return Response
     */
    public function adminInviteAssociationMessage(Request $request): Response
    {
        $this->mailer->sendInBlueEmail($_POST['email'], 13, ['MESSAGE' => '']);

        $this->addFlash('success', 'Email envoyé');

        return $this->render('admin/messages.html.twig', [
            'user' => $this->getUser(),
            'countValidations' => count($this->offersRepository->findByWorkflowState('created')),
            'countReports' => $this->reportsService->getCountOfReportedElements(),
            'contactMessages' => $this->messages->findAll(),
            'messages' => count($this->messages->findByworkflowState('active'))
        ]);
    }

    /**
     * @Route("/messages/{id}/action", name="messages_action", requirements={"id":"\d+"}, methods={"POST"})
     *
     * @param ContactMessage $message
     * @return Response
     */
    public function adminMessageAction(ContactMessage $message): Response
    {
        $em = $this->getDoctrine()->getManager();

        if ($message->getWorkFlowState() == 'active'){
            $message->setWorkflowState('read');
            $message->setModifiedAt(new \DateTime());
        } else {
            $message->setWorkflowState('active');
            $message->setModifiedAt(new \DateTime());
        }

        $em->persist($message);
        $em->flush();

        return $this->json(['message' => $message->getId()]);
    }

    /**
     * @Route("/messages/{id}/delete", name="messages_delete", requirements={"id":"\d+"}, methods={"DELETE"})
     *
     * @param ContactMessage $message
     * @return Response
     */
    public function adminDeleteMessage(ContactMessage $message): Response
    {
        $em = $this->getDoctrine()->getManager();

        $em->remove($message);
        $em->flush();

        return $this->json(['action' => 'Message deleted']);
    }

    /**
     * @Route("/reports", name="reports")
     *
     * @return Response
     */
    public function adminReports(): Response
    {
        return $this->render('admin/reports/index.html.twig', [
            'user' => $this->getUser(),
            'signaledOffers' => $this->signaledOffersRepository->findByWorkflowState('created'),
            'signaledDiscussions' => $this->signaledDiscussionsRepository->findByWorkflowState('created'),
            'countValidations' => count($this->offersRepository->findByWorkflowState('created')),
            'countReports' => $this->reportsService->getCountOfReportedElements(),
            'messages' => count($this->messages->findByworkflowState('active'))
        ]);
    }

    /**
     * @Route("/reports/discussions/{id}", name="reports_discussion", requirements={"id":"\d+"}, methods={"GET","POST"})
     *
     * @param Request $request
     * @param SignaledDiscussions $signaledDiscussion
     * @return Response
     */
    public function adminReportsDiscussionShow(Request $request, SignaledDiscussions $signaledDiscussion): Response
    {
        if ($request->IsMethod('POST')) {
            $em = $this->getDoctrine()->getManager();

            $signaledDiscussion->setWorkflowState($_POST['action']);
            $signaledDiscussion->setModifiedAt(new \DateTime());
            $em->persist($signaledDiscussion);
            $em->flush();

            return $this->json(['signaledDiscussion' => $signaledDiscussion->getId()]);
        }

        return $this->render('admin/reports/discussion.html.twig', [
            'user' => $this->getUser(),
            'signaledDiscussion' => $signaledDiscussion,
            'countValidations' => count($this->offersRepository->findByWorkflowState('created')),
            'countReports' => $this->reportsService->getCountOfReportedElements(),
            'messages' => count($this->messages->findByworkflowState('active'))
        ]);
    }

    /**
     * @Route("/reports/offers/{id}/action", name="reports_offers_action", requirements={"id":"\d+"}, methods={"POST"})
     *
     * @param SignaledOffers $article
     * @return Response
     */
    public function adminSignaledOffersAction(SignaledOffers $signaledOffers): Response
    {
        $em = $this->getDoctrine()->getManager();

        $signaledOffers->setWorkflowState($_POST['action']);
        $signaledOffers->setModifiedAt(new \DateTime());
        $em->persist($signaledOffers);
        $em->flush();

        return $this->json(['signaledOffers' => $signaledOffers->getId()]);
    }

    /**
     * Génère les datas pour le graphique de la semaine & la tendance
     *
     * @param [type] $repository
     * @return array
     */
    private function getDatas($repository): array
    {
        $firstDayLastWeek = (new \DateTime)->modify('last week')->setTime(00, 00);
        $firstDaythisWeek = (new \DateTime)->modify('this week')->setTime(00, 00);

        // Total par jour de la semaine courante
        for ($i=0; $i<7; $i++) {
            $weekDatas[(new \DateTime)->setTimestamp($firstDaythisWeek->getTimestamp())->modify('+'.$i.'day')->format('d/m')] = count(
                $repository->findByPeriod(
                    (new \DateTime)->setTimestamp($firstDaythisWeek->getTimestamp())->modify('+'.$i.'day'),
                    (new \DateTime)->setTimestamp($firstDaythisWeek->getTimestamp())->modify('+'.$i.'day')->setTime(23, 59, 59)
                )
            );
        }

        // Total de la semaine passée sur la période de la semaine courante (ex: si mercredi, total de lundi à mercredi dernier)
        $lastWeekDatas = count(
            $repository->findByPeriod(
                $firstDayLastWeek,
                (new \DateTime)->modify('- 7 days')->setTime(23, 59, 59)
            )
        );

        // Tendance par rapport à la semaine précédente
        $trend = $lastWeekDatas > array_sum($weekDatas) ? 'down' : ($lastWeekDatas == array_sum($weekDatas) ? 'equal' : 'up');

        return [
            'datas' => $weekDatas,
            'trend' => $trend
        ];
    }
}
