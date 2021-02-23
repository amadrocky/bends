<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Service\MailerService;
use App\Repository\UserRepository;

class SecurityController extends AbstractController
{
    /**
     * @param AuthenticationUtils $authenticationUtils
     * @return Response
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
         if ($this->getUser()) {
             return $this->redirectToRoute('home');
         }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'user' => $this->getUser(),
            'messages' => null
        ]);
    }

    /**
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @return Response
     * @throws \Exception
     * @Route("/register", name="app_register")
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder, MailerService $mailer): Response
    {
        $em = $this->getDoctrine()->getManager();
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if( $form->isSubmitted() && $form->isValid()) {
            $user->setPassword($passwordEncoder->encodePassword(
                $user,
                $user->getPassword()
            ));
            $user->setCreatedAt(new \DateTime());
            $user->setModifiedAt(new \DateTime());
            $user->setWorkflowState('created');
            $user->setToken($this->generateToken());
            $em->persist($user);
            $em->flush();

            $mailer->sendEmail(
                $user->getFirstname(), 
                $user->getEmail(), 
                'Validation de votre compte',
                'emails/signup.html.twig',
                $user->getToken()
            );

            $this->addFlash('success', 'Votre compte utilisateur a bien été créé. Un email de validation vous a été envoyé.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', [
            'form' => $form->createView(),
            'user' => $this->getUser(),
            'messages' => null
        ]);
    }

    /**
     * User account activation
     *
     * @Route("/activation/{token}", name="active_account", methods={"GET"})
     *
     * @param string $token
     * @param UserRepository $userRepository
     * @return RedirectResponse
     */
    public function activeAccount(string $token, UserRepository $userRepository): RedirectResponse
    {
        $user = $userRepository->findOneBy(['token' => $token]);

        if ($user) {
            $em = $this->getDoctrine()->getManager();
            $user->setToken(null);
            $user->setWorkflowstate('active');
            $user->setModifiedAt(new \DateTime());
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Votre compte utilisateur est actif.');
        }

        return $this->redirectToRoute('app_login');
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \Exception('This method can be blank - it will be intercepted by the logout key on your firewall');
    }

    private function generateToken()
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
