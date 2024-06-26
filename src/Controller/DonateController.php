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
    public function createCheckoutSession(): JsonResponse
    {
        \Stripe\Stripe::setApiKey($this->getParameter('stripe_api_key'));

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
                    'images' => ["https://bends-community.fr/build/images/Webp.net-resizeimage.png"],
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
     * @Route("/success", name="success")
     *
     * @param Request $request
     * @return Response
     */
    public function success(Request $request): Response
    {
        return $this->render('donate/success.html.twig', [
            'user' => $this->getUser(),
            'messages' => $request->getSession()->get('messages')
        ]);
    }

    /**
     * Error payment redirection
     * 
     * @Route("/error", name="error")
     *
     * @param Request $request
     * @return Response
     */
    public function error(Request $request): Response
    {
        return $this->render('donate/error.html.twig', [
            'user' => $this->getUser(),
            'messages' => $request->getSession()->get('messages')
        ]);
    }
}
