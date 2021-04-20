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

/**
 * * @Route("/admin", name="admin_")
 */
class AdminController extends AbstractController
{
    /**
     * @Route("/", name="home")
     *
     * @param OffersRepository $offersRepository
     * @param UserRepository $userRepository
     * @param AssociationsRepository $associationsRepository
     * @return Response
     */
    public function index(OffersRepository $offersRepository, UserRepository $userRepository, AssociationsRepository $associationsRepository): Response
    {
        $offersDatas = $this->getDatas($offersRepository);
        $usersDatas = $this->getDatas($userRepository);
        $associationsDatas = $this->getDatas($associationsRepository);

        $categoriesDatas = $offersRepository->getByCategories();
        $total = 0;
        foreach($categoriesDatas as $value){
            $total += $value['nbOffers'];
        }

        return $this->render('admin/index.html.twig', [
            'user' => $this->getUser(),
            'countOffers' => count($offersRepository->findByworkflowState('active')),
            'countUsers' => count($userRepository->findAll()),
            'countAsso' => count($associationsRepository->findByworkflowState('active')),
            'countValidations' => count($offersRepository->findByWorkflowState('created')),
            'offersDatas' => $offersDatas,
            'usersDatas' => $usersDatas,
            'associationsDatas' => $associationsDatas,
            'categoriesDatas' => $categoriesDatas,
            'total' => $total
        ]);
    }

    /**
     * @Route("/offers", name="offers")
     *
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @param OffersRepository $offersRepository
     * @return Response
     */
    public function adminOffers(Request $request, PaginatorInterface $paginator, OffersRepository $offersRepository): Response
    {
        $datas = $offersRepository->findBy(['workflowState' => 'active'], ['createdAt' => 'DESC']);

        $offers = $paginator->paginate($datas, $request->query->getInt('page', 1), 20);

        return $this->render('admin/offers/index.html.twig', [
            'user' => $this->getUser(),
            'offers' => $offers,
            'countValidations' => count($offersRepository->findByWorkflowState('created')),
        ]);
    }

    /**
     * @Route("/offers/validations", name="offers_validations")
     *
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @param OffersRepository $offersRepository
     * @return Response
     */
    public function adminOffersToValidate(Request $request, PaginatorInterface $paginator, OffersRepository $offersRepository): Response
    {
        $datas = $offersRepository->findByWorkflowState('created');

        $offers = $paginator->paginate($datas, $request->query->getInt('page', 1), 20);

        return $this->render('admin/offers/validations.html.twig', [
            'user' => $this->getUser(),
            'offers' => $offers,
            'countValidations' => count($offersRepository->findByWorkflowState('created')),
        ]);
    }

    /**
     * @Route("/offers/{id}", name="offers_show", requirements={"id":"\d+"})
     *
     * @param Offers $offer
     * @param AssociationsRepository $associationsRepository
     * @param OffersRepository $offersRepository
     * @return Response
     */
    public function adminOffersShow(Offers $offer, AssociationsRepository $associationsRepository, OffersRepository $offersRepository): Response
    {
        return $this->render('admin/offers/show.html.twig', [
            'user' => $this->getUser(),
            'offer' => $offer,
            'offerAssociation' => $associationsRepository->findByOffer($offer),
            'countValidations' => count($offersRepository->findByWorkflowState('created')),
        ]);
    }

    /**
     * @Route("/offers/{id}/action", name="offers_action", requirements={"id":"\d+"}, methods={"POST"})
     *
     * @param Offers $offer
     * @param MailerService $mailer
     * @return Response
     */
    public function offerAction(Offers $offer, MailerService $mailer): Response
    {
        $em = $this->getDoctrine()->getManager();

        $offer->setWorkflowState($_POST['action']);
        $offer->setModifiedAt(new \DateTime());
        $em->persist($offer);
        $em->flush();

        if ($_POST['action'] == 'active') {
            $mailer->sendEmail(
                $offer->getCreatedBy()->getFirstname(), 
                $offer->getCreatedBy()->getEmail(),
                'Votre annonce est en ligne !',
                'emails/activeOffer.html.twig'
            );
        } else {
            $mailer->sendEmail(
                $offer->getCreatedBy()->getFirstname(), 
                $offer->getCreatedBy()->getEmail(),
                'Votre annonce',
                'emails/inactiveOffer.html.twig'
            );
        }

        return $this->json(['offer' => $offer->getId()]);
    }


    /**
     * @Route("/users", name="users")
     *
     * @param UserRepository $userRepository
     * @param OffersRepository $offersRepository
     * @return Response
     */
    public function adminUsers(UserRepository $userRepository, OffersRepository $offersRepository): Response
    {
        return $this->render('admin/users/index.html.twig', [
            'user' => $this->getUser(),
            'users' => $userRepository->getUsersArray(),
            'countValidations' => count($offersRepository->findByWorkflowState('created'))
        ]);
    }

    /**
     * @Route("/users/{id}", name="users_show", requirements={"id":"\d+"})
     *
     * @param User $user
     * @param AssociationsRepository $associationsRepository
     * @param OffersRepository $offersRepository
     * @return Response
     */
    public function adminUsersShow(User $adminUser, AssociationsRepository $associationsRepository, OffersRepository $offersRepository): Response
    {
        return $this->render('admin/users/show.html.twig', [
            'user' => $this->getUser(),
            'adminUser' => $adminUser,
            'userAssociation' => $associationsRepository->findBy(['createdBy' => $adminUser->getId(), 'workflowState' => 'active']) ? $associationsRepository->findBy(['createdBy' => $adminUser->getId(), 'workflowState' => 'active'])[0] : [],
            'userOffers' => $offersRepository->findBy(['createdBy' => $adminUser->getId(), 'workflowState' => 'active'], ['createdAt' => 'DESC']),
            'countValidations' => count($offersRepository->findByWorkflowState('created'))
        ]);
    }

    /**
     * @Route("/users/{id}/action", name="users_action", requirements={"id":"\d+"}, methods={"POST"})
     *
     * @param User $user
     * @param OffersRepository $offersRepository
     * @param AssociationsRepository $associationsRepository
     * @param MailerService $mailer
     * @return Response
     */
    public function adminUsersAction(User $user, OffersRepository $offersRepository, AssociationsRepository $associationsRepository, MailerService $mailer): Response
    {
        $em = $this->getDoctrine()->getManager();

        $user->setWorkflowState($_POST['action']);
        $user->setModifiedAt(new \DateTime());
        $em->persist($user);

        $userOffers = $_POST['action'] == 'active' ? $offersRepository->findBy(['createdBy' => $user->getId(), 'workflowState' => 'inactive']) : $offersRepository->findBy(['createdBy' => $user->getId(), 'workflowState' => 'active']);
        if(count($userOffers) > 0){
            foreach($userOffers as $offer){
                $offer->setWorkFlowState($_POST['action']);
                $offer->setModifiedAt(new \DateTime());
                $em->persist($offer);
            }
        }

        $userAsso = $_POST['action'] == 'active' ? $associationsRepository->findBy(['createdBy' => $user->getId(), 'workflowState' => 'inactive']) : $associationsRepository->findBy(['createdBy' => $user->getId(), 'workflowState' => 'active']);
        if(count($userAsso) > 0){
            $userAsso[0]->setWorkFlowState($_POST['action']);
            $userAsso[0]->setModifiedAt(new \DateTime());
            $em->persist($userAsso[0]);
        }

        $em->flush();

        if ($_POST['action'] == 'active') {
            $mailer->sendEmail(
                $user->getFirstname(), 
                $user->getEmail(),
                'Informations sur votre compte',
                'emails/welcome.html.twig'
            );
        } else {
            $mailer->sendEmail(
                $user->getFirstname(), 
                $user->getEmail(),
                'Informations sur votre compte',
                'emails/inactiveUser.html.twig'
            );
        }

        return $this->json(['user' => $user->getId()]);
    }

    /**
     * @Route("/associations", name="associations")
     *
     * @param AssociationsRepository $associationsRepository
     * @param OffersRepository $offersRepository
     * @return Response
     */
    public function adminAssociations(AssociationsRepository $associationsRepository, OffersRepository $offersRepository): Response
    {
        return $this->render('admin/associations/index.html.twig', [
            'user' => $this->getUser(),
            'associations' => $associationsRepository->getAssociationsArray(),
            'countValidations' => count($offersRepository->findByWorkflowState('created'))
        ]);
    }

    /**
     * @Route("/associations/{id}", name="associations_show", requirements={"id":"\d+"})
     *
     * @param AssociationsRepository $associationsRepository
     * @param OffersRepository $offersRepository
     * @return Response
     */
    public function adminAssociationsShow(Associations $association, OffersRepository $offersRepository): Response
    {
        return $this->render('admin/associations/show.html.twig', [
            'user' => $this->getUser(),
            'association' => $association,
            'countValidations' => count($offersRepository->findByWorkflowState('created'))
        ]);
    }

    /**
     * @Route("/associations/{id}/action", name="associations_action", requirements={"id":"\d+"}, methods={"POST"})
     *
     * @param Associations $association
     * @param MailerService $mailer
     * @return Response
     */
    public function adminAssociationsAction(Associations $association, MailerService $mailer): Response
    {
        $em = $this->getDoctrine()->getManager();

        $association->setWorkflowState($_POST['action']);
        $association->setModifiedAt(new \DateTime());
        $em->persist($association);
        $em->flush();

        if ($_POST['action'] == 'active') {
            $mailer->sendEmail(
                $association->getCreatedBy()->getFirstname(), 
                $association->getCreatedBy()->getEmail(),
                'Informations sur votre association',
                'emails/activeAssociation.html.twig'
            );
        } else {
            $mailer->sendEmail(
                $association->getCreatedBy()->getFirstname(), 
                $association->getCreatedBy()->getEmail(),
                'Informations sur votre association',
                'emails/inactiveAssociation.html.twig'
            );
        }

        return $this->json(['association' => $association->getId()]);
    }

    /**
     * @Route("/articles", name="articles")
     *
     * @param ArticlesRepository $articlesRepository
     * @param OffersRepository $offersRepository
     * @return Response
     */
    public function adminArticles(ArticlesRepository $articlesRepository, OffersRepository $offersRepository): Response
    {
        return $this->render('admin/articles/index.html.twig', [
            'user' => $this->getUser(),
            'articles' => $articlesRepository->getArticlesArray(),
            'countValidations' => count($offersRepository->findByWorkflowState('created'))
        ]);
    }

    /**
     * @Route("/articles/new", name="articles_new", methods={"GET","POST"})
     *
     * @param Request $request
     * @param OffersRepository $offersRepository
     * @return Response
     */
    public function adminArticlesNew(Request $request, OffersRepository $offersRepository): Response
    {
        $article = new Articles();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            $fileName = $_FILES['imgArticle']['name'];
            $uploadDir = $_SERVER['PWD'] . '/assets/static/images/actualities/';

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
            'countValidations' => count($offersRepository->findByWorkflowState('created')),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/articles/{id}", name="articles_show", requirements={"id":"\d+"})
     *
     * @param Articles $article
     * @param OffersRepository $offersRepository
     * @return Response
     */
    public function adminArticlesShow(Articles $article, OffersRepository $offersRepository): Response
    {
        return $this->render('admin/articles/show.html.twig', [
            'user' => $this->getUser(),
            'article' => $article,
            'countValidations' => count($offersRepository->findByWorkflowState('created'))
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
     * @param OffersRepository $offersRepository
     * @param MailerService $mailer
     * @return Response
     */
    public function adminSendMessage(Request $request, OffersRepository $offersRepository, MailerService $mailer): Response
    {
        if ($request->IsMethod('POST')) {
            $mailer->sendAdminEmail(
                $_POST['email'], 
                $_POST['subject'],
                $_POST['message'],
                'emails/adminMessage.html.twig'
            );
        }

        return $this->render('admin/messages.html.twig', [
            'user' => $this->getUser(),
            'countValidations' => count($offersRepository->findByWorkflowState('created'))
        ]);
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
