<?php

namespace App\Controller;

use App\Entity\Jour;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class JourController extends AbstractController
{
    #[Route('/jour/choisir', name: 'choisir_jour', methods: ['GET', 'POST'])]
    public function choisirJour(Request $request, EntityManagerInterface $entityManager): Response
    {
        $jours = $entityManager->getRepository(Jour::class)->findAll();

        if ($request->isMethod('POST')) {
            $jeuId = $request->request->get('jeu');
            $tirageId = $request->request->get('tirage');

            // Réinitialiser tous les champs à false
            foreach ($jours as $jour) {
                $jour->setJeu(false);
                $jour->setTirage(false);
            }

            // Mettre à jour les champs sélectionnés
            if ($jeuId) {
                $jourJeu = $entityManager->getRepository(Jour::class)->find($jeuId);
                if ($jourJeu) {
                    $jourJeu->setJeu(true);
                }
            }

            if ($tirageId) {
                $jourTirage = $entityManager->getRepository(Jour::class)->find($tirageId);
                if ($jourTirage) {
                    $jourTirage->setTirage(true);
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Les jours ont été mis à jour avec succès.');
            return $this->redirectToRoute('choisir_jour');
        }

        return $this->render('jour/choisir.html.twig', [
            'jours' => $jours,
        ]);
    }
}