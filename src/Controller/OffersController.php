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
     * @param OffersRepository $offersRepository
     * @param CategoriesRepository $categoriesRepository
     * @return Response
     */
    public function index(OffersRepository $offersRepository, CategoriesRepository $categoriesRepository): Response
    {
        $regions = file_get_contents("https://geo.api.gouv.fr/regions");

        return $this->render('offers/index.html.twig', [
            'offers' => $offersRepository->findAll(),
            'user' => $this->getUser(),
            'categories' => $categoriesRepository->findAll(),
            'regions' => json_decode($regions)
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
        $offer = new Offers();
        $form = $this->createForm(OffersType::class, $offer);
        $form->handleRequest($request);
        $cities = [];

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
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $offer->setZipCode($_POST['zip']);
            $offer->setCity($_POST['filtered_cities']);
            $offer->setCreatedAt(new \DateTime());

            /* Récupération du context (dep, region) */
            foreach ($cities as $key => $city) {
                if ($city[$key]['label'] === $_POST['filtered_cities']) {
                    $offer->setContext($city[$key]['context']);
                }
            }

            /* Récupération des images */
            if (isset($_POST['img1'])) {
                /** @var UploadedFile $image1 */
                $image1 = $_POST['img1']->getData();
                $image1Name = $fileUploader->uploadOffer($image1);
                $offer->setPictures([$image1Name]);
            } elseif (isset($_POST['img2'])) {
                /** @var UploadedFile $image2 */
                $image2 = $_POST['img2']->getData();
                $image2Name = $fileUploader->uploadOffer($image2);
                $offer->setPictures([$image2Name]);
            } elseif (isset($_POST['img3'])) {
                /** @var UploadedFile $image3 */
                $image3 = $_POST['img3']->getData();
                $image3Name = $fileUploader->uploadOffer($image3);
                $offer->setPictures([$image3Name]);
            }

            $offer->setWorkflowState('created');
            $entityManager->persist($offer);
            $entityManager->flush();

            return $this->redirectToRoute('offers_index');
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
        return $this->render('offers/show.html.twig', [
            'offer' => $offer,
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
