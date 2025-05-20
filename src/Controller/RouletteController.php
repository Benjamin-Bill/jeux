<?php

namespace App\Controller;

use App\Entity\Jeux;
use App\Form\JeuxType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RouletteController extends AbstractController
{
    #[Route('/roulette', name: 'app_roulette')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $jeux = $entityManager->getRepository(Jeux::class)->findAll();
        $transmit = [];
        foreach ($jeux as $jeu) {
            $transmit[]= [
                'question' => $jeu->getNom(),
                'label' => $jeu->getNom(),
                'value' => $jeu->getPonderation(),
                'url' => $jeu->getUrl(),
                'prix' => $jeu->getPrix(),
            ];
        }

        return $this->render('roulette/index.html.twig', [
            'transmit' => $transmit,
        ]);
    }

    #[\Symfony\Component\Routing\Annotation\Route('/drop', name: 'drop_game')]
    public function drop(Request $request, EntityManagerInterface $entityManager): Response
    {
        $jeu = new Jeux();
        $form = $this->createForm(JeuxType::class, $jeu);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $nomNormalise = strtolower(str_replace(' ', '', $jeu->getNom()));

            $jeuExistant = $entityManager->getRepository(Jeux::class)
                ->findOneBy(['nom' => $nomNormalise]);

            if ($jeuExistant) {
                $jeuExistant->setPonderation($jeuExistant->getPonderation() + 1);
                $entityManager->flush();
                $this->addFlash('success', 'La pondération du jeu existant a été augmentée.');
            } else {
                $jeu->setNom($nomNormalise);
                $entityManager->persist($jeu);
                $entityManager->flush();
                $this->addFlash('success', 'Le jeu a été créé avec succès.');
            }

            return $this->redirectToRoute('drop_game');
        }

        return $this->render('roulette/drop.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
