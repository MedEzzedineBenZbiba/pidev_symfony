<?php

namespace App\Controller;

use Sun\Contract\Country;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
#[Route('/bot')]
class BotController extends AbstractController
{
    #[Route('/reservation/bot', name: 'app_bot')]
    public function index(Request $request): Response
    {
    $qa = [
        'Bonjour' => 'Bonjour ! Comment puis-je vous aider ?',
        'hotels disponibles' => 'verifier les hotels dans votre page de consultation des hotels ',
        'Quand êtes-vous disponible pour un rendez-vous ?' => 'Merci de m envoyez un sms pour plus de détails',
        'oooo'=>'Je ne comprends pas ce que vous voulez dire!',
        'j ai besoin de l aide' =>'Oui, comment puis-je vous aider ?'

           ];
    $message = $request->request->get('message');
    if (array_key_exists($message, $qa)) {
        $response = $qa[$message];
    } else {
        $response = 'SALUT!';
    }
    return $this->render('Front/bot/index.html.twig', [
        'response' => $response
    ]);

}}