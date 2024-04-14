<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Twilio\Rest\Client;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SmsController extends AbstractController
{
    #[Route('/sms', name: 'app_sms')]
    public function sendSms(): Response
    {
       
        $token = "db958c2c382e047a284d9a60b3876013";
        $phone = "";
$firstname="ons";
        $client = new Client($token);

        $message = $client->messages->create(
            "+216".$phone, // to
            array(
                "from" => "+12514514104", //modified 
                "body" => "Hello" .$firstname.
                " ".
                "We're excited to let you know that your recent order has been shipped! Your package is on its way and should be arriving soon."
            )
        );
        return $this->render('home/backhome.html.twig', [
            'controller_name' => 'AcceuilController',
        ]);
}

}