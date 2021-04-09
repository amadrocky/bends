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

/**
 * @Route("/actuality", name="actuality_")
 */
class ActualitiesController extends AbstractController
{
    /**
     * @Route("/", name="home")
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

        $lastOffers = array_slice($offersRepository->findBy(['workflowState' => 'active'], ['createdAt' => 'DESC']), 0, 3);
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

    /**
     * @Route("/addClick/{id}", name="add_click", requirements={"id":"\d+"}, methods={"POST"})
     *
     * @param integer $id
     * @param ArticlesRepository $articlesRepository
     * @return Response
     */
    public function addClick(int $id, ArticlesRepository $articlesRepository): Response
    {
        $em = $this->getDoctrine()->getManager();
        $article = $articlesRepository->find($id);

        $article->setClicks($article->getClicks() + 1);
        $em->persist($article);
        $em->flush();

        return $this->json(['article' => $article->getId()]);
    }
}
