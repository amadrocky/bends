<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Offers;
use App\Entity\User;
use App\Repository\OffersRepository;
use App\Repository\UserRepository;
use App\Repository\AssociationsRepository;
use App\Service\MailerService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;

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
            'countValidations' => count($offersRepository->findByWorkflowState('created')),
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
            'countValidations' => count($offersRepository->findByWorkflowState('created')),
        ]);
    }

    /**
     * @Route("/users/{id}/action", name="users_action", requirements={"id":"\d+"}, methods={"POST"})
     *
     * @param User $user
     * @param MailerService $mailer
     * @return Response
     */
    public function userAction(User $user, MailerService $mailer): Response
    {
        $em = $this->getDoctrine()->getManager();

        $user->setWorkflowState($_POST['action']);
        $user->setModifiedAt(new \DateTime());
        $em->persist($user);
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
