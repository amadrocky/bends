<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\AssociationsRepository;

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

        $userHasAsso = false;

        if (!empty($associationsRepository->findBy(['createdBy' => $user->getId(), 'workflowState' => 'active']))) {
            $userHasAsso = true;
        }

        return $this->render('associations/new.html.twig', [
            'user' => $user,
            'messages' => $request->getSession()->get('messages'),
            'association' => $userHasAsso
        ]);
    }
}
