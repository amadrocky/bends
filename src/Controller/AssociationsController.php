<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\AssociationsRepository;
use App\Repository\OffersRepository;
use App\Repository\CategoriesRepository;
use App\Entity\Associations;
use App\Form\AssociationType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @Route("/associations", name="associations_")
 */
class AssociationsController extends AbstractController
{
    /**
     * @Route("/", name="home")
     *
     * @param Request $request
     * @return Response
     */
    public function home(Request $request): Response
    {
        return $this->render('associations/home.html.twig', [
            'user' => $this->getUser(),
            'messages' => $request->getSession()->get('messages')
        ]);
    }

    /**
     * @Route("/all", name="index", methods={"GET"})
     *
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @param AssociationsRepository $associationsRepository
     * @return Response
     */
    public function index(Request $request, PaginatorInterface $paginator, AssociationsRepository $associationsRepository, CacheInterface $cache): Response
    {
        if (isset($_GET['location'])) {
            $datas = $associationsRepository->findByLocation($_GET['location']);
        } else {
            $datas = $associationsRepository->findBy(['workflowState' => 'active'], ['modifiedAt' => 'DESC']);
        }

        $associations = $paginator->paginate(
            $datas, //on passe les données
            $request->query->getInt('page', 1), //numéro de la page en cours, 1 par défaut
            10// nombre d'éléments
        );

        /* Mise en cache du resultat des régions (écriture si pas de réponse) */
        $regions = $cache->get('regions', function(ItemInterface $item) {
            $item->expiresAfter(\DateInterval::createFromDateString('1 day')); // Durée de mise en cache
            return file_get_contents("https://geo.api.gouv.fr/regions");
        });

        return $this->render('associations/index.html.twig', [
            'associations' => $associations,
            'user' => $this->getUser(),
            'messages' => $request->getSession()->get('messages'),
            'regions' => json_decode($regions),
        ]);
    }

    /**
     * * @Route("/offers", name="offers", methods={"GET"})
     *
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @param OffersRepository $offersRepository
     * @param CategoriesRepository $categoriesRepository
     * @return Response
     */
    public function offers(Request $request, PaginatorInterface $paginator, OffersRepository $offersRepository, CategoriesRepository $categoriesRepository, CacheInterface $cache): Response
    {
        $datas = $offersRepository->findByAssociations();
        
        if(isset($_GET['search'])) {
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

            $datas = $offersRepository->getSearchResults($search, $category, $location, true);
        }

        $offers = $paginator->paginate(
            $datas, //on passe les données
            $request->query->getInt('page', 1), //numéro de la page en cours, 1 par défaut
            20// nombre d'éléments
        );

        /* Mise en cache du resultat des régions (écriture si pas de réponse) */
        $regions = $cache->get('regions', function(ItemInterface $item) {
            $item->expiresAfter(\DateInterval::createFromDateString('1 day')); // Durée de mise en cache
            return file_get_contents("https://geo.api.gouv.fr/regions");
        });

        return $this->render('associations/offers.html.twig', [
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
     * Add a new association
     * 
     * @Route("/new", name="new")
     *
     * @param Request $request
     * @param AssociationsRepository $associationsRepository
     * @return Response
     */
    public function new(Request $request, AssociationsRepository $associationsRepository): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $userHasAsso = !empty($associationsRepository->findBy(['createdBy' => $user->getId(), 'workflowState' => 'active']));
        $waitingValidation = !empty($associationsRepository->findBy(['createdBy' => $user->getId(), 'workflowState' => 'created']));

        $session = $request->getSession();
        $association = new Associations();
        $form = $this->createForm(AssociationType::class, $association);
        $form->handleRequest($request);
        $cities = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $association->setZipCode($_POST['zip']);
            $association->setCity($_POST['filtered_cities']);
            $association->setCreatedAt(new \DateTime());

            /* Récupération du context (dep, region) */
            $cities = $session->get('cities');
            foreach ($cities as $city) {
                if ($city['name'] === $_POST['filtered_cities']) {
                    $association->setContext($city['context']);
                }
            }

            /* Récupération de l'image */
            $uploadDir = $_SERVER['PWD'] . '/assets/static/images/associations/';
            $file = null;

            if (isset($_FILES['img'])) {
                $fileName = $_FILES['img']['name'];

                if ($fileName !== "") {
                    /* On renomme l'image */
                    $extention = strrchr($fileName, ".");
                    $fileName = 'association_image_' . uniqid() . $extention;

                    $uploadFile = $uploadDir . basename($fileName);
                    move_uploaded_file($_FILES['img']['tmp_name'], $uploadFile);
                    $file = basename($uploadFile);
                }
            }

            $association->setPicture($file);
            $association->setCreatedBy($this->getUser());
            $association->setModifiedAt(new \Datetime());
            $association->setWorkflowState('active');
            $entityManager->persist($association);
            $entityManager->flush();

            $this->addFlash('success', 'Votre association a bien été enregistrée');

            return $this->redirectToRoute('associations_index');
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

        return $this->render('associations/new.html.twig', [
            'user' => $user,
            'messages' => $request->getSession()->get('messages'),
            'association' => $userHasAsso,
            'form' => $form->createView(),
            'cities' => $cities,
            'waitingValidation' => $waitingValidation,
            'userAssociation' => $associationsRepository->findBy(['createdBy' => $user->getId(), 'workflowState' => 'active']) ? $associationsRepository->findBy(['createdBy' => $user->getId(), 'workflowState' => 'active'])[0] : []
        ]);
    }

    /**
     *@Route("/{id}", name="show", requirements={"id":"\d+"})
     * 
     * @param Request $request
     * @param Associations $association
     * @return Response
     */
    public function show(Request $request, Associations $association, OffersRepository $offersRepository): Response
    {
        return $this->render('associations/show.html.twig', [
            $user = $this->getUser(),
            'association' => $association,
            'user' => $user,
            'messages' => $request->getSession()->get('messages'),
            'today' => new \DateTime(),
            'yesterday' => (new \DateTime())->modify('-1 day'),
            'associationOffers' => $offersRepository->findByAssociation($association)
        ]);
    }

    /**
     * 
     * @Route("/edit/{id}", name="edit", requirements={"id":"\d+"}, methods={"POST"})
     *
     * @param Request $request
     * @param Associations $association
     * @return Response
     */
    public function edit(Request $request, Associations $association): Response
    {
        $session = $request->getSession();
        $entityManager = $this->getDoctrine()->getManager();
        $fileName = $_FILES['img']['name'];

        if ($_POST['name'] !== $association->getName()) {
            $association->setName($_POST['name']);
        }

        if ($_POST['description'] !== $association->getDescription()) {
            $association->setDescription($_POST['description']);
        }

        if ($_POST['link'] !== $association->getLink()) {
            $association->setLink($_POST['link']);
        }

        if ($fileName !== "") {
            /* Récupération de l'image */
            $uploadDir = $_SERVER['PWD'] . '/assets/static/images/associations/';
            $file = null;

            /* On renomme l'image */
            $extention = strrchr($fileName, ".");
            $fileName = 'association_image_' . uniqid() . $extention;

            $uploadFile = $uploadDir . basename($fileName);
            move_uploaded_file($_FILES['img']['tmp_name'], $uploadFile);
            $file = basename($uploadFile);
            
            if ($association->getPicture() !== null) {
                // Supprime l'ancien fichier
                unlink($uploadDir . $association->getPicture());
            }

            $association->setPicture($fileName);
        }

        if ($_POST['zip'] !== "") {
            $association->setZipCode(intval($_POST['zip']));
            $association->setCity($_POST['filtered_cities']);
            
            /* Récupération du context (dep, region) */
            $cities = $session->get('cities');
            foreach ($cities as $city) {
                if ($city['name'] === $_POST['filtered_cities']) {
                    $association->setContext($city['context']);
                }
            }
        }

        $association->setModifiedAt(new \DateTime());

        $entityManager->persist($association);
        $entityManager->flush();

        $this->addFlash('success', 'Modification(s) enregistrée(s)');

        return $this->redirectToRoute('profil_index');
    }

    /**
     * @Route("/delete/{id}", name="delete", requirements={"id":"\d+"}, methods="DELETE")
     *
     * @param Request $request
     * @param Associations $association
     * @return Response
     */
    public function delete(Request $request, Associations $association): Response
    {
        if ($this->isCsrfTokenValid('delete'.$association->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $association->setWorkflowState('deleted');
            $em->flush();
        } else {
            return $this->redirectToRoute('home');
        }

        $this->addFlash('success', 'Association supprimée');
        return $this->redirectToRoute('profil_index');
    }
}
