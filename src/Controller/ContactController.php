<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Form\ContactFormType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact')]
    public function contact(ManagerRegistry $doctrine,Request $request): Response
    {
        $contacto = new Contact();
        $formulario = $this->createForm(ContactFormType::class,$contacto);
        $formulario->handleRequest($request);

                if($formulario->isSubmitted() && $formulario->isValid()){
                    $contacto = $formulario->getData();
                    $entityManager = $doctrine->getManager();
                    $entityManager->persist($contacto);
                    $entityManager->flush();
                    return $this->redirectToRoute("thankyou");
                }
                return $this->render('contact.html.twig',[
                    'form' => $formulario->createView()
                ]);
    }
}
