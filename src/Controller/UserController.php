<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\AssociationsRepository;
use App\Repository\OffersRepository;
use App\Entity\User;

/**
 * @Route("/app/profil", name="profil_")
 */
class UserController extends AbstractController
{
    public function __construct(AssociationsRepository $associationsRepository, OffersRepository $offersRepository)
    {
        $this->associationsRepository = $associationsRepository;
        $this->offersRepository = $offersRepository;
    }

    /**
     * @Route("/", name="index")
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $user = $this->getUser();
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

            $session->set('cities', $cities);
        }

        return $this->render('user/profil.html.twig', [
            'user' => $user,
            'messages' => $request->getSession()->get('messages'),
            'userAssociation' => $this->associationsRepository->findBy(['createdBy' => $user->getId(), 'workflowState' => 'active']) ? $this->associationsRepository->findBy(['createdBy' => $user->getId(), 'workflowState' => 'active'])[0] : [],
            'cities' => $cities,
            'userOffers' => $this->offersRepository->findBy(['createdBy' => $user->getId(), 'workflowState' => 'active'], ['createdAt' => 'DESC'])
        ]);
    }

    /**
     * @Route("/edit/{id}", name="edit", requirements={"id":"\d+"}, methods={"POST"})
     *
     * @param Request $request
     * @param User $user
     * @return RedirectResponse
     */
    public function edit(Request $request, User $user): RedirectResponse
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $cities = [];
        $entityManager = $this->getDoctrine()->getManager();
        $fileName = $_FILES['imgProfile']['name'];
        $uploadDir = '/var/www/bends/bends/assets/static/images/profil/';

        if ($_POST['lastname'] !== $user->getLastname()) {
            $user->setLastname($_POST['lastname']);
        }

        if ($_POST['firstname'] !== $user->getFirstname()) {
            $user->setLastname($_POST['firstname']);
        }

        if ($_POST['pseudonym'] !== $user->getPseudonym()) {
            $user->setPseudonym($_POST['pseudonym']);
        }

        if (new \DateTime($_POST['birthDate']) !== $user->getDateOfBirth()) {
            $user->setDateOfBirth(new \DateTime($_POST['birthDate']));
        }

        if ($_POST['gender'] !== $user->getGender()) {
            $user->setGender($_POST['gender']);
        }

        if (isset($_POST['deleteFile'])) {
            // Supprime l'ancien fichier
            unlink($uploadDir . $user->getProfilImage());
            $user->setProfilImage(null);
        }

        if ($fileName !== "") {
            $file = null;

            /* On renomme l'image */
            $extention = strrchr($fileName, ".");
            $fileName = 'profil_image_' . uniqid() . $extention;

            $uploadFile = $uploadDir . basename($fileName);
            move_uploaded_file($_FILES['imgProfile']['tmp_name'], $uploadFile);
            $file = basename($uploadFile);
            
            if ($user->getProfilImage() !== null) {
                // Supprime l'ancien fichier
                unlink($uploadDir . $user->getProfilImage());
            }

            $user->setProfilImage($fileName);
        }

        $user->setModifiedAt(new \DateTime());
        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', 'Modification(s) enregistrée(s)');

        return $this->redirectToRoute('profil_index');
    }

    /**
     * @Route("/delete/{id}", name="delete", requirements={"id":"\d+"}, methods="DELETE")
     *
     * @param Request $request
     * @param User $user
     * @return RedirectResponse
     */
    public function delete(Request $request, User $user): RedirectResponse
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $uploadDir = '/var/www/bends/bends/assets/static/images/profil/';
            
            foreach ($user->getOffers() as $offer) {
                $user->removeOffer($offer);
            }

            foreach ($user->getAssociations() as $association) {
                $user->removeAssociation($association);
            }

            foreach ($user->getMessages() as $message) {
                $user->removeMessage($message);
            }

            foreach ($user->getDiscussions() as $discussion) {
                $user->removeDiscussion($discussion);
            }

            if ($user->getProfilImage() !== null) {
                // Supprime le fichier image stocké
                unlink($uploadDir . $user->getProfilImage());
            }

            $em->remove($user);
            $em->flush();
        } else {
            return $this->redirectToRoute('home');
        }

        $this->container->get('security.token_storage')->setToken(null);

        $this->addFlash('success', 'Votre compte utilisateur a bien été supprimé');
        return $this->redirectToRoute('home');
    }
}
