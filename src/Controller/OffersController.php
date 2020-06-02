<?php

namespace App\Controller;

use App\Entity\Offers;
use App\Form\OffersType;
use App\Repository\CategoriesRepository;
use App\Repository\OffersRepository;
use App\Service\FileUploader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/offers")
 */
class OffersController extends AbstractController
{
    /**
     * @Route("/", name="offers_index", methods={"GET"})
     *
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @param OffersRepository $offersRepository
     * @param CategoriesRepository $categoriesRepository
     * @return Response
     */
    public function index(Request $request, PaginatorInterface $paginator, OffersRepository $offersRepository, CategoriesRepository $categoriesRepository): Response
    {
        $datas = $offersRepository->findBy([], ['createdAt' => 'desc']);

        $offers = $paginator->paginate(
            $datas, //on passe les données
            $request->query->getInt('page', 1), //numéro de la page en cours, 1 par défaut
            20 // nombre d'éléments
        );

        $regions = file_get_contents("https://geo.api.gouv.fr/regions");

        return $this->render('offers/index.html.twig', [
            'offers' => $offers,
            'user' => $this->getUser(),
            'categories' => $categoriesRepository->findAll(),
            'regions' => json_decode($regions),
            'today' => new \DateTime(),
            'yesterday' => (new \DateTime())->modify('-1 day')
        ]);
    }

    /**
     * @Route("/new", name="offers_new", methods={"GET","POST"})
     * @param Request $request
     * @param FileUploader $fileUploader
     * @return Response
     * @throws \Exception
     */
    public function new(Request $request, FileUploader $fileUploader): Response
    {
        $session = $request->getSession();
        $offer = new Offers();
        $form = $this->createForm(OffersType::class, $offer);
        $form->handleRequest($request);
        $cities = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $offer->setZipCode($_POST['zip']);
            $offer->setCity($_POST['filtered_cities']);
            $offer->setCreatedAt(new \DateTime());

            /* Récupération du context (dep, region) */
            $cities = $session->get('cities');
            foreach ($cities as $city) {
                if ($city['name'] === $_POST['filtered_cities']) {
                    $offer->setContext($city['context']);
                }
            }

            /* Récupération des images */
            $uploadDir = $_SERVER['PWD'] . '/assets/static/images/offers/';
            $files = [];

            for ($i = 1; $i < 6; $i++) {
                if (isset($_FILES['img' . $i])) {
                    if ($_FILES['img' . $i]['name'] !== "") {
                        $uploadFile = $uploadDir . basename($_FILES['img' . $i]['name']);
                        move_uploaded_file($_FILES['img' . $i]['tmp_name'], $uploadFile);
                        $files[] = basename($uploadFile);
                    }   
                }
            }

            $offer->setPictures($files);

            $offer->setWorkflowState('created');
            $entityManager->persist($offer);
            $entityManager->flush();

            $this->addFlash('success', 'Votre annonce a bien été enregistrée');

            return $this->redirectToRoute('offers_index');
        }

        if ($request->IsMethod('POST')) {
            $apiRequest = json_decode(
                file_get_contents(
                    'https://api-adresse.data.gouv.fr/search/?q=' . intval($request->request->get('zipCode'))
                ), true
            );

            foreach ($apiRequest['features'] as $key => $value) {
                if ($value['properties']['type'] === 'municipality') {
                    $cities[$key]['id'] = $value['properties']['id'];
                    $cities[$key]['name'] = $value['properties']['label'];
                    $cities[$key]['context'] = $value['properties']['context'];
                }
            }

            $session->set('cities', $cities);
        }

        return $this->render('offers/new.html.twig', [
            'offer' => $offer,
            'form' => $form->createView(),
            'user' => $this->getUser(),
            'cities' => $cities
        ]);
    }

    /**
     * @Route("/{id}", name="offers_show", methods={"GET"})
     * @param Offers $offer
     * @return Response
     */
    public function show(Offers $offer): Response
    {
        $apiRequest = json_decode(
            file_get_contents(
                'https://api-adresse.data.gouv.fr/search/?q=' . $offer->getZipCode()
            ), true
        );

        $coordinates = $apiRequest['features'][0]['geometry']['coordinates'];

        return $this->render('offers/show.html.twig', [
            'offer' => $offer,
            'user' => $this->getUser(),
            'coordinates' => $coordinates,
            'today' => new \DateTime(),
            'yesterday' => (new \DateTime())->modify('-1 day')
        ]);
    }

    /**
     * @Route("/{id}/edit", name="offers_edit", methods={"GET","POST"})
     * @param Request $request
     * @param Offers $offer
     * @return Response
     */
    public function edit(Request $request, Offers $offer): Response
    {
        $form = $this->createForm(OffersType::class, $offer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('offers_index');
        }

        return $this->render('offers/edit.html.twig', [
            'offer' => $offer,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="offers_delete", methods={"DELETE"})
     * @param Request $request
     * @param Offers $offer
     * @return Response
     */
    public function delete(Request $request, Offers $offer): Response
    {
        if ($this->isCsrfTokenValid('delete' . $offer->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($offer);
            $entityManager->flush();
        }

        return $this->redirectToRoute('offers_index');
    }
}
