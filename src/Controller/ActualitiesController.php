<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use App\Repository\ArticlesRepository;
use App\Repository\OffersRepository;
use App\Repository\AssociationsRepository;

class ActualitiesController extends AbstractController
{
    /**
     * @Route("/actuality", name="actuality")
     *
     * @param Request $request
     * @param ArticlesRepository $articlesRepository
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function index(Request $request, ArticlesRepository $articlesRepository, OffersRepository $offersRepository,AssociationsRepository $associationsRepository, PaginatorInterface $paginator): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $datas = $articlesRepository->findBy(['workflowState' => 'active'], ['createdAt' => 'DESC']);

        $articles = $paginator->paginate(
            $datas, //on passe les données
            $request->query->getInt('page', 1), //numéro de la page en cours, 1 par défaut
            5// nombre d'éléments
        );

        $lastOffers = array_slice($offersRepository->findBy(['workflowState' => 'created'], ['createdAt' => 'DESC']), 0, 3);
        $lastAssociations = array_slice($associationsRepository->findBy(['workflowState' => 'active'], ['modifiedAt' => 'DESC']), 0, 3);

        return $this->render('actualities/index.html.twig', [
            'user' => $this->getUser(),
            'articles' => $articles,
            'lastOffers' => $lastOffers,
            'lastAssociations' => $lastAssociations,
            'today' => new \DateTime(),
            'yesterday' => (new \DateTime())->modify('-1 day')
        ]);
    }
}
