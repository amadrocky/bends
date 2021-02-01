<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\Discussions;
use App\Entity\Offers;
use App\Entity\Favorites;
use App\Entity\Research;
use App\Entity\SignaledOffers;
use App\Form\MessageType;
use App\Form\OffersType;
use App\Form\SignalOfferType;
use App\Repository\CategoriesRepository;
use App\Repository\OffersRepository;
use App\Repository\UserRepository;
use App\Repository\DiscussionsRepository;
use App\Repository\FavoritesRepository;
use App\Service\FileUploader;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\QrCode;

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
            20// nombre d'éléments
        );

        $regions = file_get_contents("https://geo.api.gouv.fr/regions");

        return $this->render('offers/index.html.twig', [
            'offers' => $offers,
            'user' => $this->getUser(),
            'messages' => $request->getSession()->get('messages'),
            'categories' => $categoriesRepository->findAll(),
            'regions' => json_decode($regions),
            'today' => new \DateTime(),
            'yesterday' => (new \DateTime())->modify('-1 day'),
        ]);
    }

    /**
     * @Route("/new", name="offers_new", methods={"GET","POST"})
     *
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    function new (Request $request): Response 
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

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
                    $fileName = $_FILES['img' . $i]['name'];

                    if ($fileName !== "") {
                        /* On renomme l'image */
                        $extention = strrchr($fileName, ".");
                        $fileName = 'offer_image_' . uniqid() . $extention;

                        $uploadFile = $uploadDir . basename($fileName);
                        move_uploaded_file($_FILES['img' . $i]['tmp_name'], $uploadFile);
                        $files[] = basename($uploadFile);
                    }
                }
            }

            $offer->setPictures($files);
            $offer->setCreatedBy($this->getUser());
            $offer->setWorkflowState('created');
            $entityManager->persist($offer);
            $entityManager->flush();

            // Create a basic QR code
            $qrCode = new QrCode('http://127.0.0.1:8000/offers/' . $offer->getId());
            $qrCode->setSize(300);

            // Set advanced options
            $qrCode->setWriterByName('png');
            $qrCode->setEncoding('UTF-8');
            $qrCode->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0]);
            $qrCode->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255]);
            $qrCode->setValidateResult(false);

            // name qrCode
            $qrCodeName = uniqId() . '.png';

            // Save it to a file
            $qrCode->writeFile($_SERVER['PWD'] . '/assets/static/images/qrCodes/'. $qrCodeName);

            $offer->setQrCode($qrCodeName);
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
            'messages' => $session->get('messages'),
            'offer' => $offer,
            'form' => $form->createView(),
            'user' => $this->getUser(),
            'cities' => $cities,
        ]);
    }

    /**
     * @Route("/{id}", name="offers_show", requirements={"id":"\d+"})
     *
     * @param Request $request
     * @param Offers $offer
     * @param DiscussionsRepository $discussionsRepository
     * @return Response
     */
    public function show(Request $request, Offers $offer, DiscussionsRepository $discussionsRepository, FavoritesRepository $favoritesRepository): Response
    {
        $discussion = new Discussions();
        $message = new Message();
        $now = new \DateTime();
        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);
        $isFavorite = false;
        if ($this->getUser()){
            $isFavorite = count($favoritesRepository->findByUserAndOffer($this->getUser(), $offer)) > 0;
        }
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            /** Cas où une discussion existe déja entre les utilisateurs sur l'offre. */
            $existingDiscussion = $discussionsRepository->findByUsersAndOffer($this->getUser(), $offer->getCreatedBy(), $offer);
            if ($existingDiscussion === null) {
                $discussion->setCreatedBy($this->getUser());
                $discussion->setCreatedAt($now);
                $discussion->setModifiedAt($now);
                $discussion->setWorkflowState('created');
                $discussion->setOffer($offer);
                $discussion->setUser($offer->getCreatedBy());
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

            $message->setCreatedAt($now);
            $message->setCreatedBy($this->getUser());
            $message->setWorkflowState('created');
            $message->setDiscussion($discussion);
            $entityManager->persist($message);
            $entityManager->flush();

            $this->addFlash('success', 'Votre message a bien été envoyé');
            return $this->redirectToRoute('offers_show', ['id' => $offer->getId()]);
        }

        $apiRequest = json_decode(
            file_get_contents(
                'https://api-adresse.data.gouv.fr/search/?q=' . $offer->getZipCode()
            ), true
        );

        $coordinates = $apiRequest['features'][0]['geometry']['coordinates'];

        /** Ajout aux favoris */
        if ($request->IsMethod('POST')) {
            $favorite = new Favorites();
            $entityManager = $this->getDoctrine()->getManager();
            $favorite->setUser($this->getUser());
            $favorite->setOffer($offer);
            $favorite->setCreatedAt(new \DateTime());
            $favorite->setWorkflowstate('created');
            $entityManager->persist($favorite);
            $entityManager->flush();
        }

        return $this->render('offers/show.html.twig', [
            'offer' => $offer,
            'user' => $this->getUser(),
            'isFavorite' => $isFavorite,
            'messages' => $request->getSession()->get('messages'),
            'coordinates' => $coordinates,
            'today' => new \DateTime(),
            'yesterday' => (new \DateTime())->modify('-1 day'),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/research", name="offers_research", methods={"GET"})
     *
     * @param Request $request
     * @param OffersRepository $offersRepository
     * @param PaginatorInterface $paginator
     * @param CategoriesRepository $categoriesRepository
     * @return Response
     */
    public function research(Request $request, OffersRepository $offersRepository, PaginatorInterface $paginator, CategoriesRepository $categoriesRepository): Response
    {
        $search = $_GET['search'];
        $category = $_GET['category'];
        $location = $_GET['location'];

        if ($this->getUser()) {
            if ($search === '' && $category === 'allCat' && $location === 'allReg') {
                
            } else {
                $research = new Research();
                $em = $this->getDoctrine()->getManager();
                $research->setUser($this->getUser());
                if ($category === 'allCat') {
                    $research->setCategory(null);
                } else {
                    $research->setCategory($categoriesRepository->find($category));
                }
                $research->setSearch($search);
                if ($location === 'allReg') {
                    $research->setLocation(null);
                } else {
                    $research->setLocation($location);
                }
                $research->setCreatedAt(new \DateTime());
                $research->setWorkflowState('created');
                $em->persist($research);
                $em->flush();

                /* Limite des 5 dernières recherches par utilisateur */
                if (count($this->getUser()->getResearches()->getValues()) > 5) {
                    $em->remove($this->getUser()->getResearches()->getValues()[0]);
                    $em->flush();
                }
            }
        }

        $datas = $offersRepository->getSearchResults($search, $category, $location);

        $results = $paginator->paginate(
            $datas, //on passe les données
            $request->query->getInt('page', 1), //numéro de la page en cours, 1 par défaut
            20// nombre d'éléments
        );

        $regions = file_get_contents("https://geo.api.gouv.fr/regions");

        return $this->render('offers/research.html.twig', [
            'categories' => $categoriesRepository->findAll(),
            'regions' => json_decode($regions),
            'user' => $this->getUser(),
            'messages' => $request->getSession()->get('messages'),
            'results' => $results,
            'today' => new \DateTime(),
            'yesterday' => (new \DateTime())->modify('-1 day')
        ]);
    }

    /**
     * @Route("/research/last", name="offers_lastResearches", methods={"GET"})
     *
     * @param Request $request
     * @return Response
     */
    public function lastResearches(Request $request) :Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $researches = array_reverse($this->getUser()->getResearches()->getValues());

        return $this->render('offers/lastResearches.html.twig', [
            'user' => $this->getUser(),
            'messages' => $request->getSession()->get('messages'),
            'researches' => $researches,
            'today' => new \DateTime(),
            'yesterday' => (new \DateTime())->modify('-1 day')
        ]);
    }

    /**
     * @Route("/favorites", name="favorites", methods={"GET"})
     *
     * @param Request $request
     * @param FavoritesRepository $messageRepository
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function favorites(Request $request, FavoritesRepository $favoritesRepository, PaginatorInterface $paginator): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $datas = $favoritesRepository->findByUser($this->getUser());

        $favorites = $paginator->paginate(
            $datas, //on passe les données
            $request->query->getInt('page', 1), //numéro de la page en cours, 1 par défaut
            10// nombre d'éléments
        );

        return $this->render('offers/favorites.html.twig', [
            'favorites' => $favorites,
            'user' => $this->getUser(),
            'messages' => $request->getSession()->get('messages'),
            'today' => new \DateTime(),
            'yesterday' => (new \DateTime())->modify('-1 day')
        ]);
    }

    /**
     * @Route("/favorite/deleteSelection", name="favorites_deleteSelection")
     *
     * @param DiscussionsRepository $discussionsRepository
     * @return void
     */
    public function deleteSelection(FavoritesRepository $favoritesRepository)
    {
        $entityManager = $this->getDoctrine()->getManager();

        if (empty($_POST['favorites'])) {
            $this->addFlash('error', 'Aucun favoris séléctionné');

            $this->redirectToRoute('favorites');
        } else {
            foreach ($_POST['favorites'] as $favoriteId) {
                $favorite = $favoritesRepository->find($favoriteId);
                $entityManager->remove($favorite);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Favori(s) supprimé(s)');
        }

        return $this->redirectToRoute('favorites');
    }

    /**
     * @Route("/signalOffer/{id}", name="offers_signal", requirements={"id":"\d+"})
     *
     * @param Offer $offer
     * @param Request $request
     * @return Response
     */
    public function signalOffer(Offers $offer, Request $request) :Response
    {
        $signaledOffer = new SignaledOffers();
        $form = $this->createForm(SignalOfferType::class, $signaledOffer);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $signaledOffer->setOffer($offer);
            $signaledOffer->setCreatedAt(new \DateTime());
            $signaledOffer->setWorkflowState('created');

            if ($this->getUser()) {
                $signaledOffer->setCreatedBy($this->getUser());
            }

            $entityManager->persist($signaledOffer);
            $entityManager->flush();

            $this->addFlash('info', 'Annonce signalée. Nos équipes prennent le relais et vous souhaite une bonne navigation sur notre site.');
            return $this->redirectToRoute('offers_index');
        }

        return $this->render('offers/signalOffer.html.twig', [
            'offer' => $offer,
            'user' => $this->getUser(),
            'messages' => $request->getSession()->get('messages'),
            'today' => new \DateTime(),
            'yesterday' => (new \DateTime())->modify('-1 day'),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/export/{id}", name="offer_export", requirements={"id":"\d+"})
     *
     * @param Offers $offer
     * @return void
     */
    public function exportOffer(Offers $offer): void
    {
        // Configure Dompdf according to your needs
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isRemoteEnabled', true);
        
        // Instantiate Dompdf with our options
        $dompdf = new Dompdf($pdfOptions);
        
        // Retrieve the HTML generated in our twig file
        $html = $this->renderView('PDF/exportOffer.html.twig', [
            'offer' => $offer
        ]);

        // Load HTML to Dompdf
        $dompdf->loadHtml(($html));
        
        // (Optional) Setup the paper size and orientation 'portrait' or 'portrait'
        $dompdf->setPaper('A4', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();

        // Output the generated PDF to Browser (inline view)
        $dompdf->stream($offer->getTitle() . '.pdf', [
            "Attachment" => false
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
