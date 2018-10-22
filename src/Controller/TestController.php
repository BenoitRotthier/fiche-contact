<?php

// src/Controller/TestController.php
namespace App\Controller;

use App\Entity\Contact;
use App\Entity\Form;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends Controller
{
    /**
     * @Route("/test")
     */
    public function new(Request $request, \Swift_Mailer $mailer)
    {
        $entity = new Form();

        $form = $this->createFormBuilder($entity)
            ->add('nom', TextType::class, array('label' => 'Nom'))
            ->add('prenom', TextType::class, array('label' => 'Prénom'))
            ->add('categorie', ChoiceType::class, array(
                'choices'  => array(
                    'Direction' => 'direction',
                    'Ressources humaines' => 'rh',
                    'Développement' => 'dev',
                ),
            ))
            ->add('objet', TextType::class, array('label' => 'Objet'))
            ->add('message', TextareaType::class, array('label' => 'Message'))
            ->add('save',SubmitType::class, array('label' => 'Envoyer'))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $task = $form->getData();

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($form->getData());
            $entityManager->flush();

            $repository = $this->getDoctrine()->getRepository(Contact::class);

            $email = $repository->findBy(['categorie' => $form["categorie"]->getData()]);

            foreach ($email as $mail) {
                $mail->getEmail();
                $message = (new \Swift_Message($form["objet"]->getData()))
                    ->setFrom('benoit59179@gmail.com')
                    ->setTo($mail->getEmail())
                    ->setBody($this->renderView(
                        'test/mailcontent.html.twig',
                        array('message' => $form["message"]->getData()),
                        'text/html'));


                $mailer->send($message);
            }
            return $this->render('test/mailsent.html.twig');
        }

        return $this->render('test/new.html.twig', array(
            'form' => $form->createView(),
        ));
    }
}