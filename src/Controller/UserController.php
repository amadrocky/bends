<?php

namespace App\Controller;

use App\Form\ProfilImageType;
use App\Service\FileUploader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\AssociationsRepository;
use App\Repository\OffersRepository;

class UserController extends AbstractController
{
    /**
     * @param Request $request
     * @param FileUploader $fileUploader
     * @return Response
     * @Route("/profil", name="profil")
     */
    public function index(Request $request, FileUploader $fileUploader, AssociationsRepository $associationsRepository, OffersRepository $offersRepository) :Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $user = $this->getUser();
        $form = $this->createForm(ProfilImageType::class);
        $form->handleRequest($request);
        $cities = [];

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageProfil */
            $imageProfil = $form['profilImage']->getData();

            $profilFileName = $fileUploader->upload($imageProfil);

            // suppression de l'ancienne image
            /*if ($user->getProfilImage()){
                unlink('assets/static/images/profil/' . $user->getProfilImage());
            }*/

            $em = $this->getDoctrine()->getManager();
            $user->setProfilImage($profilFileName);
            $user->setModifiedAt(new \DateTime());
            $em->flush();

            $this->addFlash('success', 'Image de profil mise à jour');
            return $this->redirect($this->generateUrl('profil'));
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

        return $this->render('user/profil.html.twig', [
            'user' => $user,
            'messages' => $request->getSession()->get('messages'),
            'form' => $form->createView(),
            'userAssociation' => $associationsRepository->findBy(['createdBy' => $user->getId(), 'workflowState' => 'active']) ? $associationsRepository->findBy(['createdBy' => $user->getId(), 'workflowState' => 'active'])[0] : [],
            'cities' => $cities,
            'userOffers' => $offersRepository->findBy(['createdBy' => $user->getId(), 'workflowState' => 'created'], ['createdAt' => 'DESC'])
        ]);
    }
}
