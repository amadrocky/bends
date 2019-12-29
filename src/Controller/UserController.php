<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    /**
     * @Route("/profil", name="profil")
     */
    public function index()
    {
        if (!$this->getUser()) {
            $this->addFlash('error', 'Accès interdit, merci de vous identifier.');
            return $this->redirectToRoute('home');
        }

        return $this->render('user/profil.html.twig', [
            'user' => $this->getUser(),
        ]);
    }
}
