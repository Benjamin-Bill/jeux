<?php

namespace App\Security;

use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;

class DiscordAuthenticator extends OAuth2Authenticator
{
    private ClientRegistry $clientRegistry;
    private RouterInterface $router;
    private SessionInterface $session;
    private UrlGeneratorInterface $urg;
    private EntityManagerInterface $entityManager;


    public function __construct(
        ClientRegistry $clientRegistry,
        RouterInterface $router,
        SessionInterface $session,
        UrlGeneratorInterface $urg,
        EntityManagerInterface $entityManager
    ) {
        $this->clientRegistry = $clientRegistry;
        $this->router = $router;
        $this->session = $session;
        $this->urg = $urg;
        $this->entityManager = $entityManager;
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_discord_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('discord');

        // Obtenez l'objet AccessToken
        $accessToken = $client->getAccessToken();
        $this->session->getFlashBag()->add('info', 'AccessToken récupéré : ' . json_encode($accessToken));

        // Récupérez les données utilisateur sous forme de tableau
        $discordUser = $client->fetchUserFromToken($accessToken);
        $discordData = $discordUser->toArray();
        $this->session->getFlashBag()->add('info', 'Utilisateur Discord récupéré : ' . json_encode($discordData));

        // Logique d'authentification avec UserBadge
        return new Passport(
            new UserBadge($discordData['id'], function ($userIdentifier) use ($discordData) {
                // Vérifiez si l'utilisateur existe dans la base de données
                $user = $this->entityManager->getRepository(\App\Entity\User::class)
                    ->findOneBy(['discordId' => $userIdentifier]);

                if (!$user) {
                    // Créez un nouvel utilisateur si nécessaire
                    $user = new \App\Entity\User();
                    $user->setDiscordId($discordData['id']);
                    $user->setAvatar($discordData['avatar']);
                    $user->setPseudo($discordData['username']);

                    // Sauvegardez l'utilisateur dans la base de données
                    $this->entityManager->persist($user);
                    $this->entityManager->flush();
                }
                $this->session->getFlashBag()->add('info', 'Utilisateur badge récupéré : ' . json_encode($user));
                return $user;
            }),
            new CustomCredentials(
                function () {
                    return true;
                },
                $accessToken
            ),
            [new RememberMeBadge()]
        );
    }

    public function getUser($credentials, UserProviderInterface $userProvider): ?UserInterface
    {
        try {
            $discordUser = $this->clientRegistry->getClient('discord')->fetchUserFromToken($credentials);

            // Vérifiez si l'utilisateur existe dans la base de données
            $user = $userProvider->loadUserByIdentifier($discordUser->getId());


            if (!$user) {
                // Créez un nouvel utilisateur si nécessaire
                $user = new \App\Entity\User();
                $user->setDiscordId($discordUser->getId());
                // Ajoutez d'autres champs si nécessaire

                // Sauvegardez l'utilisateur dans la base de données
                $entityManager = $this->entityManager;
                $entityManager->persist($user);
                $entityManager->flush();
            }

            return $user;
        } catch (\Exception $e) {
            $this->session->getFlashBag()->add('error', 'Erreur lors de la récupération de l\'utilisateur : ' . $e->getMessage());
            return null;
        }
    }

    public function onAuthenticationSuccess(Request $request, $token, string $firewallName): ?Response
    {
        return new RedirectResponse($this->urg->generate('app_roulette'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->session->getFlashBag()->add('error', 'Échec de l\'authentification : ' . $exception->getMessageKey());
        return new RedirectResponse($this->urg->generate('default'));

        #return new Response('Échec de l\'authentification : ' . $exception->getMessageKey(), Response::HTTP_FORBIDDEN);
    }
}