<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DiscordController extends AbstractController
{
    #[Route('/connect/discord', name: 'connect_discord_start')]
    public function connect(ClientRegistry $clientRegistry): Response
    {
        // Redirige l'utilisateur vers Discord
        return $clientRegistry->getClient('discord')->redirect([
            'identify',
            'email',
        ]);
    }

    #[Route('/connect/discord/check', name: 'connect_discord_check')]
    public function connectCheck(Request $request): Response
    {
        // Cette méthode est appelée après la redirection de Discord
        $user = $this->getUser();

        // Ajoutez votre logique ici (par exemple, rediriger ou afficher un message)
        return $this->redirectToRoute('homepage');
    }
}