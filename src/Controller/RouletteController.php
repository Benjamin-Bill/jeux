<?php

namespace App\Controller;

use App\Entity\Jeux;
use App\Entity\Jour;
use App\Form\JeuxType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RouletteController extends AbstractController
{
    #[Route('/roulette', name: 'app_roulette')]
    public function index(EntityManagerInterface $entityManager,Request $request): Response
    {
        $jeux = $entityManager->getRepository(Jeux::class)->findAll();
        $jours = $entityManager->getRepository(Jour::class)->findAll();


        $transmit = [];
        $i=0;

        $form = $this->createFormBuilder()
            ->add('maxPlayers', NumberType::class, [
                'required' => false,
                'label' => 'Nombre maximum de joueurs',
            ])
            ->add('maxPrice', NumberType::class, [
                'required' => false,
                'label' => 'Prix maximum',
            ])
            ->add('submit', SubmitType::class, ['label' => 'Filtrer les jeux'])
            ->getForm();
        $form->handleRequest($request);
        $maxPlayers = null;
        $maxPrice = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $maxPlayers = $data['maxPlayers'];
            $maxPrice = $data['maxPrice'];
        }
        $tirageActif = false;
        $prochainTirage = null;
        foreach ($jours as $jour) {
            if ($jour->isTirage()) {
                $tirageActif = true;

                // Calculer le prochain tirage (19h à 22h)
                $parisTimeZone = new \DateTimeZone('Europe/Paris');
                $now = new \DateTime('now', $parisTimeZone);
                $prochainTirage = new \DateTime('today 15:00', $parisTimeZone);

                if ($now > new \DateTime('today 22:00', $parisTimeZone)) {
                    $prochainTirage = new \DateTime('tomorrow 19:00', $parisTimeZone);
                }
                break;
            }
        }
        foreach ($jeux as $jeu) {

            $transmit[]= [
                'question' => str_replace('_', ' ', $jeu->getNom()),
                'label' => str_replace('_', ' ', $jeu->getNom()),
                'value' => $jeu->getPonderation(),
                'url' => $jeu->getUrl(),
                'prix' => $jeu->getPrix(),
                'user' => $jeu->getUsers()[0]->getPseudo(),
                'avatar' => $jeu->getUsers()[0]->getAvatarImage(),
                'place' => $i
            ];
            $i++;
        }
        $taille = count($transmit);
        $jeu = $this->selectGame($jeux, $maxPlayers, $maxPrice);

        $rand = null;
        foreach ($transmit as $index => $item) {
            if ($item['label'] === $jeu->getNom()) {
                $rand = $index;
                break;
            }
        }


        return $this->render('roulette/index.html.twig', [
            'transmit' => $transmit,
            'rand' => $rand,
            'tirageActif' => $tirageActif,
            'prochainTirage' => $prochainTirage,
            'form' => $form->createView(),
        ]);
    }

    #[\Symfony\Component\Routing\Annotation\Route('/drop', name: 'drop_game')]
    public function drop(Request $request, EntityManagerInterface $entityManager): Response
    {
        $jeu = new Jeux();
        $form = $this->createForm(JeuxType::class, $jeu);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $nomNormalise = strtolower(str_replace(' ', '_', $jeu->getNom()));

            $jeuExistant = $entityManager->getRepository(Jeux::class)
                ->findOneBy(['nom' => $nomNormalise]);
            $userIdentifier=$this->getuser()->getUserIdentifier();
            $user = $entityManager->getRepository(\App\Entity\User::class)
                ->findOneBy(['discordId' => $userIdentifier]);
            if ($jeuExistant) {
                if ($jeuExistant->getUsers()->contains($user)) {
                    $this->addFlash('error', 'Vous avez déjà ajouté ce jeu.');
                    return $this->redirectToRoute('drop_game');
                }else {
                    $jeuExistant->setPonderation($jeuExistant->getPonderation() + 1);
                    $entityManager->flush();
                    $this->addFlash('success', 'La pondération du jeu existant a été augmentée.');
                }
            } else {

                $jeu->setNom($nomNormalise);
                $jeu->addUser($user);
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

    public function selectGame(array $games, ?int $maxPlayers = null, ?float $maxPrice = null): ?Jeux
    {
        // Filtrer les jeux selon les critères
        $filteredGames = array_filter($games, function (Jeux $game) use ($maxPlayers, $maxPrice) {
            $isValid = true;

            // Vérifier que le maxPlayer du jeu est supérieur ou égal à la valeur entrée
            if ($maxPlayers !== null) {
                $isValid = $isValid && ($game->getMaxPlayer() === null || $game->getMaxPlayer() >= $maxPlayers);
            }

            if ($maxPrice !== null) {
                $isValid = $isValid && ($game->getPrix() <= $maxPrice);
            }

            return $isValid;
        });

        // Si aucun jeu ne correspond, retourner null
        if (empty($filteredGames)) {
            return null;
        }

        // Tirer un jeu aléatoire parmi les jeux filtrés
        return $filteredGames[array_rand($filteredGames)];
    }

    #[Route('/validate', name: 'validate_game', methods: ['POST'])]
    public function validateGame(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->addFlash('error', 'entré dans la méthode.');

        $gameId = $request->request->get('game_id');
        $jeu = str_replace(' ', '_', $gameId);
        $jeu = ltrim(str_replace(' ', '_', $gameId), '_');
        $this->addFlash('error', 'le jeu : .'.$jeu);
        $jeu = $entityManager->getRepository(Jeux::class)->findOneBy(['nom' => $jeu]);
        if (!$jeu) {
            $this->addFlash('error', 'Jeu non trouvé.');
            return $this->redirectToRoute('app_roulette');
        }

        $valide = new \App\Entity\Valide();
        $valide->setJeu($jeu);
        $jour = $entityManager->getRepository(\App\Entity\Jour::class)->findOneBy(['jeu' => true]);
        if (!$jour) {
            $this->addFlash('error', 'Aucun jour avec le jeu activé trouvé.');
            return $this->redirectToRoute('app_roulette');
        }

// Convertir le jour en une date valide
        $jourNom = $jour->getJour();

        // Calculer le dimanche prochain à minuit (heure de Paris)
        $parisTimeZone = new \DateTimeZone('Europe/Paris');
        $nextSunday = new \DateTime('next '.$jourNom, $parisTimeZone);
        $nextSunday->setTime(0, 0, 0);

        $valide->setDate($nextSunday);

        $entityManager->persist($valide);
        $entityManager->flush();

        $this->addFlash('success', 'Le jeu a été validé pour le dimanche prochain.');
        return $this->redirectToRoute('app_roulette');
    }
}
