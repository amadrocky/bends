<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Offers;
use App\Repository\OffersRepository;
use App\Repository\UserRepository;
use App\Repository\AssociationsRepository;
use App\Service\MailerService;

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
     * @param OffersRepository $offersRepository
     * @return Response
     */
    public function adminOffers(OffersRepository $offersRepository): Response
    {
        return $this->render('admin/offers/index.html.twig', [
            'user' => $this->getUser(),
            'allOffers' => $offersRepository->findBy(['workflowState' => 'active'], ['createdAt' => 'DESC'])
        ]);
    }

    /**
     * @Route("/offers/{id}", name="offers_show", requirements={"id":"\d+"})
     *
     * @param Offers $offer
     * @param AssociationsRepository $associationsRepository
     * @return Response
     */
    public function adminOffersShow(Offers $offer, AssociationsRepository $associationsRepository): Response
    {
        return $this->render('admin/offers/show.html.twig', [
            'user' => $this->getUser(),
            'offer' => $offer,
            'offerAssociation' => $associationsRepository->findByOffer($offer)
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
     * @return Response
     */
    public function adminUsers(UserRepository $userRepository): Response
    {
        return $this->render('admin/users/index.html.twig', [
            'user' => $this->getUser(),
            'users' => $userRepository->getUsersArray()
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
