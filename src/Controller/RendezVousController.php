<?php

namespace App\Controller;

use App\Entity\RendezVous;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry;
use App\Form\RendezVousType;
use App\Form\RendezVousBackType;
use App\Repository\ClientRepository as ClientRepository;
use App\Repository\MedecinRepository;
use App\Repository\RendezVousRepository;
use Knp\Component\Pager\PaginatorInterface;
use App\Repository\UserRepository;
use App\Service\SmsGenerator;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use DateTime;



class RendezVousController extends AbstractController
{
    #[Route('/rendez/vous', name: 'app_rendez_vous')]
    public function index(): Response
    {
        return $this->render('rendez_vous/index.html.twig', [
            'controller_name' => 'RendezVousController',
        ]);
    }

   

    #[Route('/addRendezVousFront', name: 'front_rendezVous_add')]
    public function addRendezVous(Request $request, ManagerRegistry $doctrine, 
    UserRepository $userRepository, MedecinRepository $medecinRepository, SmsGenerator $smsGenerator,
    MailerInterface $mailer): Response
    {
        $entityManager = $doctrine->getManager();
        // creates a doctor object and initializes some data for this example
        $rendezVous = new RendezVous();
        // get user by session
        $client = $this->getUser();
      
      // Check if the user is authenticated
   
        // Now you can access user information
        $username = $client->getPrenomPersonne();
        dump($username);
        $email = $client->getEmail();
        // You can access other properties or methods of your User entity
        dump($email);
     
        $rendezVous->setUser($client);
        $events = $client->getLesRendezVous();
        $rdvs = [
        ];

        foreach($events as $event){
            
            
            $rdvs[] = [
                'id' => $event->getRefRendezVous(),
                'title' => "Dr ".$event->getIdMedecin()->getNomMedecin(),
                'start' => $event->getDateRendezVous()->format('Y-m-d H:i:s'),
                'end' => date_modify($event->getDateRendezVous(),"30 minutes")->format('Y-m-d H:i:s'),
            ];
        }

        $data = json_encode($rdvs);

        
        // $rendezVous->setIdPersonne($client);
        $entityManager->persist($rendezVous);
        $form = $this->createForm(RendezVousType::class, $rendezVous, ['medecinRepository' => $medecinRepository]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid())
        {
            // $form->get('id_personne')->setData(34); // Set id_personne to 34
            // holds the submitted values
            // but, the original `$task` variable has also been updated
            $rendezVous = $form->getData();
            // TODO ... perform some action, such as saving the task to the database
            $entityManager->flush();
            // return $this->redirectToRoute('app_medecin_getAll');
            $medecinNumber = $rendezVous->getIdMedecin()->getNumeroTelephoneMedecin();
            dump($medecinNumber);
            $medecinNom = $rendezVous->getIdMedecin()->getNomMedecin();
            dump($medecinNom);
            $dateRendezVous = $rendezVous->getDateRendezVous();
            dump($dateRendezVous);
            $stringDate = $dateRendezVous->format('d/m/Y'); // for example
            $body = "tu auras un rendez-vous le  ". $stringDate;
            $smsGenerator->SendSms("+4915510686794",$medecinNom, $body);

            // Mailing -------------------
           
            // Define variables
            $clientName = $client->getPrenomPersonne();
            $doctorName = $rendezVous->getIdMedecin()->getNomMedecin();

            // HTML content with variables
            $htmlContent = '<html>
                    <body>
                        <p>Bonjour Mr/Mme ' . $clientName . '!<br>
                        Votre réservation a été confirmée avec Dr. ' . $doctorName . '
                        <br>
                        <a href="http://127.0.0.1:8000/Rvbyclient/"> Vous pouvez trouver tous vos rendez-vous ici </a>
                        </p>
                       
                        
                    </body> 
                </html>';

            // Create email
            $file = 'img/logo_city.png';
            if (file_exists($file)) {
                $handle = fopen($file, 'r');
                // Your code to embed the file...
                fclose($handle);
            } else {
                echo "File '$file' does not exist.";
            }

            $email = (new Email())
            ->from('testpi3a8@outlook.com')
            ->to('benzbibaezzdine@gmail.com')
            ->subject('Confirmation de réservation .')
            ->html($htmlContent)
            ->embed(fopen($file, 'r'), 'logo', 'image/png');
            $mailer->send($email);


            return $this->redirectToRoute('front_rendezVous_getAll');
        }

        

        return $this->render('Front/rendez_vous/addRendezVous.html.twig', [
            'controller_name' => 'RendezVousController',
            'form' => $form->createView(),
            'data' => $data,

        ]);
    }

    #[Route('/Rvbyclient', name: 'front_rendezVous_getAll')]
    public function showAllRendezVousBySession(RendezVousRepository $rendezVousRepository,
    ContainerInterface $container ,UserRepository $userRepository,
    Request $request, PaginatorInterface $paginator): Response
    {

        // $client = $userRepository->find(65);
        $client = $this->getUser();
        $query =  $client->getLesRendezVous();
        $lesRendezVousByClient = $paginator->paginate(
            
            $query,
            $request->query->getInt('page', 1), // Current page number
        3 // Items per page
        );
        return $this->render('Front/rendez_vous/showRV.html.twig', [
            'controller_name' => 'RendezVousController',
            'lesRVdeClient' => $lesRendezVousByClient,
            'container' => $container,
            'query' => $paginator,
        ]);
    }

    #[Route('/deleteRVFront/{id}', name: 'front_rendezVous_delete')]
    public function deleteRendezVous(Request $request, ManagerRegistry $doctrine, RendezVousRepository $rendezVousRepository, int $id): Response
    {
        $entityManager = $doctrine->getManager();
        // creates a doctor object and initializes some data for this example
        dump($id);

        $rendezVous = $rendezVousRepository->find($id);
        dump($rendezVous);

        
        // $rendezVous->setIdPersonne($client);
        $entityManager->remove($rendezVous);
        $entityManager->flush();

        return $this->redirectToRoute('front_rendezVous_getAll');
    }


    #[Route('/editRVFront/{id}', name: 'front_rendezVous_edit')]
    public function editRendezVousbyclient(Request $request, ManagerRegistry $doctrine, RendezVous $rendezVous ,
    SmsGenerator $smsGenerator, MailerInterface $mailer): Response
    {
        $form = $this->createForm(RendezVousType::class, $rendezVous);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $doctrine->getManager();
            // $entityManager->persist($product), but it isn't necessary: 
            // Doctrine is already "watching" your object for changes.
            $entityManager->flush();
            $this->addFlash('success', 'post.updated_successfully');
             // Define variables
            //  $clientName = $rendezVous->getClient()->getPersonne()->getNomPersonne();
             $doctorName = $rendezVous->getIdMedecin()->getNomMedecin();
             $dateRendezVous = $rendezVous->getDateRendezVous();
             $stringDate = $dateRendezVous->format('d/m/Y'); // for example
            $body = "tu auras un rendez-vous le  ". $stringDate;
            $smsGenerator->SendSms("+4915510686794",$doctorName, $body);
 
             // HTML content with variables
             $htmlContent = '<html>
                     <body>
                         <p>Bonjour Mr/Mme ' . "" . '!<br>
                         Votre réservation a été confirmée avec Dr. ' . $doctorName . '
                         <br>
                         <a href="http://127.0.0.1:8000/Rvbyclient/"> Vous pouvez trouver tous vos rendez-vous ici </a>
                         </p>
 
                     </body> 
                 </html>';
 
             // Create email
             $file = 'img/logo_city.png';
            if (file_exists($file)) {
                $handle = fopen($file, 'r');
                // Your code to embed the file...
                fclose($handle);
            } else {
                echo "File '$file' does not exist.";
            }

             $email = (new Email())
             ->from('testpi3a8@outlook.com')
             ->to('benzbibaezzdine@gmail.com')
             ->subject('Confirmation de modification de réservation .')
             ->html($htmlContent)
             ->embed(fopen($file, 'r'), 'logo', 'image/png');
             $mailer->send($email);

            return $this->redirectToRoute('front_rendezVous_getAll');
        }
        
        return $this->render('Front/rendez_vous/editRendezVous.html.twig', [
            'rendezvous' => $rendezVous,
            'form' => $form->createView(),
        ]);

      
    }



    
    #[Route('/allRVExist', name: 'back_rendezVous_getAll')]
    public function showAllRendezVousForAdmin(RendezVousRepository $rendezVousRepository,MedecinRepository $medecinRepository ,UserRepository $userRepository): Response
    {
        $allRVInDB = $rendezVousRepository->findAll();
        dump($allRVInDB);

        return $this->render('Back/rendezVous/showAllRvInOtherForm.html.twig', [
            'controller_name' => 'RendezVousController',
            'lesRVdeClient' => $allRVInDB,
        ]);
    }


      #[Route('/addRendezVousBack', name: 'back_rendezVous_add')]
    public function addRendezVousBack(Request $request, ManagerRegistry $doctrine, UserRepository $userRepository, 
                                        MedecinRepository $medecinRepository, SmsGenerator $smsGenerator, 
                                        RendezVousRepository $rendezVousRepository, MailerInterface $mailer): Response
    {
        $entityManager = $doctrine->getManager();
        // creates a doctor object and initializes some data for this example
        $rendezVous = new RendezVous();

        // $personne = $doctrine->getRepository(Personne::class)->find(55);
        // dump($personne);

        // $client = $clientRepository->find($personne);
        // dump($client);
        // $rendezVous->setIdPersonne($client);
        $events = $rendezVousRepository->findAll();
        // dd($events);
        // $rendezVous->setIdPersonne($client);
        $entityManager->persist($rendezVous);
        $form = $this->createForm(RendezVousBackType::class, $rendezVous, ['medecinRepository' => $medecinRepository], ['userRepository' => $userRepository],['entityManager' => $entityManager]);
        $form->handleRequest($request);
        $rdvs = [
        ];

        foreach($events as $event){
            
            
            $rdvs[] = [
                'id' => $event->getRefRendezVous(),
                'title' => "Dr ".$event->getIdMedecin()->getNomMedecin(),
                'start' => $event->getDateRendezVous()->format('Y-m-d H:i:s'),
                'end' => date_modify($event->getDateRendezVous(),"30 minutes")->format('Y-m-d H:i:s'),
                // 'id_personne' => $event->getClient()->getPersonne()->getNomPersonne(),

            ];
        }

        $data = json_encode($rdvs);
        if ($form->isSubmitted() && $form->isValid())
        {
            // $form->get('id_personne')->setData(34); // Set id_personne to 34
            // holds the submitted values
            // but, the original `$task` variable has also been updated
            $rendezVous = $form->getData();
            // TODO ... perform some action, such as saving the task to the database
            $entityManager->flush();
            // return $this->redirectToRoute('app_medecin_getAll');
            $medecinNumber = $rendezVous->getIdMedecin()->getNumeroTelephoneMedecin();
            dump($medecinNumber);
            $medecinNom = $rendezVous->getIdMedecin()->getNomMedecin();
            dump($medecinNom);
            $dateRendezVous = $rendezVous->getDateRendezVous();
            dump($dateRendezVous);
            $stringDate = $dateRendezVous->format('d/m/Y'); // for example
            $body = "tu auras un rendez-vous le  ". $stringDate;
            $smsGenerator->SendSms("+4915510686794",$medecinNom, $body);

            // mailing 
            
            // Define variables
            $clientName = $rendezVous->getUser()->getNomPersonne();
            $doctorName = $rendezVous->getIdMedecin()->getNomMedecin();

            // HTML content with variables
            $htmlContent = '<html>
                    <body>
                        <p>Bonjour Mr/Mme ' . $clientName . '!<br>
                        Votre réservation a été confirmée avec Dr. ' . $doctorName . '
                        <br>
                        <a href="http://127.0.0.1:8000/Rvbyclient/"> Vous pouvez trouver tous vos rendez-vous ici </a>
                        </p>
                       
                        
                    </body> 
                </html>';

            // Create email
            $file = 'img/logo_city.png';

            if (file_exists($file)) {
                $handle = fopen($file, 'r');
                // Your code to embed the file...
                fclose($handle);
            } else {
                echo "File '$file' does not exist.";
            }
            $email = (new Email())
            ->from('testpi3a8@outlook.com')
            ->to('benzbibaezzdine@gmail.com')
            ->subject('Confirmation de réservation .')
            ->html($htmlContent)
            ->embed(fopen($file, 'r'), 'logo', 'image/png');
            $mailer->send($email);
            return $this->redirectToRoute('back_rendezVous_getAll');
        }
        // if ($form->isSubmitted() && !$form->isValid()) {
        //     // Dump all form errors
        //     dump($form->getErrors(true));
        // }

        return $this->render('Back/rendezVous/addRendezVousBack.html.twig', [
            'controller_name' => 'RendezVousController',
            'form' => $form->createView(),
            'data' => $data

        ]);
    }
    
    #[Route('/editRVBack/{id}', name: 'back_rendezVous_edit')]
    public function editRendezVousBack(Request $request, ManagerRegistry $doctrine, RendezVous $rendezVous ,RendezVousRepository $rendezVousRepository, int $id): Response
    {
        $form = $this->createForm(RendezVousBackType::class, $rendezVous);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $doctrine->getManager();
            // $entityManager->persist($product), but it isn't necessary: 
            // Doctrine is already "watching" your object for changes.
            $entityManager->flush();
            $this->addFlash('success', 'post.updated_successfully');

            return $this->redirectToRoute('back_rendezVous_getAll');
        }
        
        return $this->render('Back/rendezVous/editRendezVousBack.html.twig', [
            'rendezvous' => $rendezVous,
            'form' => $form->createView(),
        ]);

      
    }
    #[Route('/rendezvous/searchFront', name: 'front_app_rv_search')]
    public function searchFront(Request $request,UserRepository $userRepository ,RendezVousRepository $rendezVousRepository, PaginatorInterface $paginator): Response
    {
        $query = $request->query->get('query');
        $client = $this->getUser();
        if ($query) {
            $qb = $rendezVousRepository->createQueryBuilder('r');
            $qb->leftJoin('r.id_medecin', 'm')
                ->where($qb->expr()->like("DATE_FORMAT(r.dateRendezVous, '%d/%m/%Y')", ':date'))
                ->orWhere($qb->expr()->like('m.nomMedecin', ':nomMedecin'))
                ->andWhere($qb->expr()->eq('r.user', ':user'))
                ->setParameter('date', '%' . $query . '%') 
                ->setParameter('nomMedecin', $query . '%') 
                ->setParameter('user', $client ) 
                ->getQuery()
                ->getResult();


            // $listrendezVous = $rendezVousRepository->createQueryBuilder('r')
            //     ->join('r.id_medecin', 'm')
            //     ->where('m.nomMedecin LIKE :query')
            //     ->setParameter('query', $query . '%')
            //     ->getQuery()
            //     ->getResult();



            $lesRVdeClient = $paginator->paginate(
                // $listrendezVous, 
                $qb,
                $request->query->getInt('page', 1), 
                10 
            );

        } else {
            return $this->redirectToRoute('front_rendezVous_getAll');

            // $query = $rendezVousRepository->findAll(); // Assuming you have a custom query method in your repository
            // $lesRVdeClient = $paginator->paginate(
            //     $query,
            // $request->query->getInt('page', 1), // Current page number
            // 4 // Items per page
            //  );
        }
        return $this->render('Front/rendez_vous/showRV.html.twig', [
            'lesRVdeClient' => $lesRVdeClient,
        ]);
    }

    #[Route('/rendezvous/search', name: 'app_rv_search')]
    public function search(Request $request, RendezVousRepository $rendezVousRepository, PaginatorInterface $paginator): Response
    {
        $query = $request->query->get('query');
        if ($query) {
            $qb = $rendezVousRepository->createQueryBuilder('r');
            $qb->leftJoin('r.id_medecin', 'm')
                ->where($qb->expr()->like("DATE_FORMAT(r.dateRendezVous, '%d/%m/%Y')", ':date'))
                ->orWhere($qb->expr()->like('m.nomMedecin', ':nomMedecin'))
                ->setParameter('date', '%' . $query . '%') 
                ->setParameter('nomMedecin', $query . '%') 
                ->getQuery()
                ->getResult();


            // $listrendezVous = $rendezVousRepository->createQueryBuilder('r')
            //     ->join('r.id_medecin', 'm')
            //     ->where('m.nomMedecin LIKE :query')
            //     ->setParameter('query', $query . '%')
            //     ->getQuery()
            //     ->getResult();



            $lesRVdeClient = $paginator->paginate(
                // $listrendezVous, 
                $qb,
                $request->query->getInt('page', 1), 
                10 
            );
        } else {
            return $this->redirectToRoute('back_rendezVous_getAll');

            // $query = $rendezVousRepository->findAll(); // Assuming you have a custom query method in your repository
            // $lesRVdeClient = $paginator->paginate(
            //     $query,
            // $request->query->getInt('page', 1), // Current page number
            // 4 // Items per page
            //  );
        }

        return $this->render('Back/rendezVous/showAllRvInOtherForm.html.twig', [
            'lesRVdeClient' => $lesRVdeClient,
        ]);
    }

    
    #[Route('/rv/tri', name: 'app_rv_tri')]
public function tri(Request $request, RendezVousRepository $rendezVousRepository, PaginatorInterface $paginator): Response
{
    $order = $request->query->get('order', 'asc');
    $field = $request->query->get('field', 'date');

    if (!in_array(strtolower($order), ['asc', 'desc'])) {
        $order = 'asc';
    }

    if (!in_array($field, ['date', 'nomMedecin'])) {
        $field = 'date';
    }

    $queryBuilder = $rendezVousRepository->createQueryBuilder('r');
    $queryBuilder->leftJoin('r.id_medecin', 'm');

    if ($field === 'date') {
        $queryBuilder->orderBy('r.dateRendezVous', $order);
    } else {
        $queryBuilder->orderBy('m.nomMedecin', $order);
    }

    $rendezvous = $queryBuilder->getQuery()->getResult();

    return $this->render('Back/rendezVous/showAllRvInOtherForm.html.twig', [
        'lesRVdeClient' => $rendezvous,
    ]);
}

#[Route('/rv/triclient', name: 'front_app_rv_tri')]
public function triforClient(Request $request, RendezVousRepository $rendezVousRepository, 
 UserRepository $userRepository ,PaginatorInterface $paginator): Response
{
    $order = $request->query->get('order', 'asc');
    $field = $request->query->get('field', 'date');
    $client = $this->getUser();
    // $idPersonne = $request->query->get('idPersonne'); // Récupération de l'ID de la personne

    if (!in_array(strtolower($order), ['asc', 'desc'])) {
        $order = 'asc';
    }

    if (!in_array($field, ['date', 'nomMedecin'])) {
        $field = 'date';
    }

    $queryBuilder = $rendezVousRepository->createQueryBuilder('r');
    $queryBuilder->leftJoin('r.id_medecin', 'm')
                 ->where($queryBuilder->expr()->eq('r.user', ':user')) // Ajout de la condition sur l'ID de la personne
                 ->setParameter('user', $client); // Passage de la valeur du paramètre

    if ($field === 'date') {
        $queryBuilder->orderBy('r.dateRendezVous', $order);
    } else {
        $queryBuilder->orderBy('m.nomMedecin', $order);
    }

    $rendezvous = $queryBuilder->getQuery()->getResult();
    $lesRVdeClient = $paginator->paginate(
        // $listrendezVous, 
        $rendezvous,
        $request->query->getInt('page', 1), 
        6
    );

    return $this->render('Front/rendez_vous/showRV.html.twig', [
        'lesRVdeClient' => $lesRVdeClient,
    ]);
}



    


    #[Route('/deleteRVBack/{id}', name: 'back_rendezVous_delete')]
    public function deleteRendezVousfromAdmin(Request $request, ManagerRegistry $doctrine, RendezVousRepository $rendezVousRepository, int $id): Response
    {
        $entityManager = $doctrine->getManager();
        // creates a doctor object and initializes some data for this example
        dump($id);

        $rendezVous = $rendezVousRepository->find($id);
        dump($rendezVous);

        
        // $rendezVous->setIdPersonne($client);
        $entityManager->remove($rendezVous);
        $entityManager->flush();

        return $this->redirectToRoute('back_rendezVous_getAll');
    }


      // Calender -------------------------
      /**
     * @Route("/api/{id}/edit", name="api_event_edit", methods={"PUT"})
     */
    public function majEvent(?RendezVous $rendezVous, Request $request, ManagerRegistry $doctrine)
    {
        // On récupère les données
        $donnees = json_decode($request->getContent());

        // if(
        //     isset($donnees->title) && !empty($donnees->title) &&
        //     isset($donnees->start) && !empty($donnees->start) &&
        //     isset($donnees->description) && !empty($donnees->description) &&
        //     isset($donnees->backgroundColor) && !empty($donnees->backgroundColor) &&
        //     isset($donnees->borderColor) && !empty($donnees->borderColor) &&
        //     isset($donnees->textColor) && !empty($donnees->textColor)
        // ){
            // Les données sont complètes
            // On initialise un code
            $code = 200;

            // On vérifie si l'id existe
            if(!$rendezVous){
                // On instancie un rendez-vous
                $rendezVous = new RendezVous;

                // On change le code
                $code = 201;
            }

            // On hydrate l'objet avec les données
            $rendezVous->setDateRendezVous(new DateTime($donnees->start));

            $em =  $doctrine->getManager();;
            $em->persist($rendezVous);
            $em->flush();

            // On retourne le code
            return new Response('Ok', $code);
        // }else{
        //     // Les données sont incomplètes
        //     return new Response('Données incomplètes', 404);
        // }


        return $this->redirectToRoute('back_rendezVous_add');
    }

}
