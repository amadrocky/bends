<?php

namespace App\Controller;

use App\Form\ProfilImageType;
use App\Service\FileUploader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    /**
     * @param Request $request
     * @param FileUploader $fileUploader
     * @return Response
     * @Route("/profil", name="profil")
     */
    public function index(Request $request, FileUploader $fileUploader) :Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $user = $this->getUser();
        $form = $this->createForm(ProfilImageType::class);
        $form->handleRequest($request);

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

        return $this->render('user/profil.html.twig', [
            'user' => $user,
            'form' => $form->createView()
        ]);
    }
}
