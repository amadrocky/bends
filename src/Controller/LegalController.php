<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/legal", name="legal_")
 */
class LegalController extends AbstractController
{
    /**
     * @Route("/mentions", name="mentions")
     * 
     * @param Request $request
     * @return Response
     */
    public function legalMentions(Request $request): Response
    {
        return $this->render('legal/mentions.html.twig', [
            'user' => $this->getUser(),
            'messages' => $request->getSession()->get('messages')
        ]);
    }

    /**
     * @Route("/CGU", name="CGU")
     *
     * @param Request $request
     * @return Response
     */
    public function displayConditions(Request $request): Response
    {
        return $this->render('legal/CGU.html.twig', [
            'user' => $this->getUser(),
            'messages' => $request->getSession()->get('messages')
        ]);
    }
}
