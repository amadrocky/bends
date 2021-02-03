<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\AssociationsRepository;
use App\Entity\Associations;
use App\Form\AssociationType;

/**
 * @Route("/associations", name="associations_")
 */
class AssociationsController extends AbstractController
{
    /**
     * @Route("/", name="index")
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        return $this->render('associations/index.html.twig', [
            'user' => $this->getUser(),
            'messages' => $request->getSession()->get('messages')
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
            $association->setWorkflowState('created');
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
            'userAssociation' => $associationsRepository->findBy(['createdBy' => $user->getId(), 'workflowState' => 'active'])[0]
        ]);
    }
}
