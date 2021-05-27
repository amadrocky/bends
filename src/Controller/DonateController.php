<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Stripe\Stripe;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/donate", name="donate_")
 */
class DonateController extends AbstractController
{
    /**
     * @Route("/", name="index")
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        return $this->render('donate/index.html.twig', [
            'user' => $this->getUser(),
            'messages' => $request->getSession()->get('messages')
        ]);
    }

    /**
     * @Route("/create-checkout-session", name="create_checkout_session", methods={"GET", "POST"})
     *
     * @param integer $amount
     * @param string $apiSecretKey
     * @return JsonResponse
     */
    public function createCheckoutSession(
        string $apiSecretKey = 'sk_test_51I7qCgIjktDIYiezUfNYo411jpXTPey9JPxQBzojqxMJxHKmUA6XN2czkq5r4dGieTTSZytFtYosvhLReG1m3z3E00GDzfPTIn'
    ): JsonResponse
    {
        \Stripe\Stripe::setApiKey($apiSecretKey);

        $amount = $_GET['amount'] * 100;

        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                'name' => 'Don bends-community.fr',
                ],
                'unit_amount' => $amount,
                'product_data' => [
                    'name' => 'Donation bends-community.fr',
                    'images' => ["https://bends-community.fr/build/icons/violetBtn.png"],
                ],
            ],
            'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => 'https://bends-community.fr/donate/success',
            'cancel_url' => 'https://bends-community.fr/donate/error',
        ]);

        return new JsonResponse([ 'id' => $session->id ], 200);
    }

    /**
     * Success payment redirection
     * 
     * @Route("/donate/success", name="success")
     *
     * @return Response
     */
    public function success(): Response
    {
        return $this->render('donate/success.html.twig', [
            'user' => $this->getUser(),
            'messages' => $request->getSession()->get('messages')
        ]);
    }

    /**
     * Error payment redirection
     * 
     * @Route("/donate/error", name="error")
     *
     * @return Response
     */
    public function error(): Response
    {
        return $this->render('donate/error.html.twig', [
            'user' => $this->getUser(),
            'messages' => $request->getSession()->get('messages')
        ]);
    }
}
