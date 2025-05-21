<?php

namespace App\Entity;

use App\Repository\JourRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JourRepository::class)]
class Jour
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $Jour = null;

    #[ORM\Column]
    private ?bool $tirage = null;

    #[ORM\Column]
    private ?bool $jeu = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJour(): ?string
    {
        return $this->Jour;
    }

    public function setJour(string $Jour): static
    {
        $this->Jour = $Jour;

        return $this;
    }

    public function isTirage(): ?bool
    {
        return $this->tirage;
    }

    public function setTirage(bool $tirage): static
    {
        $this->tirage = $tirage;

        return $this;
    }

    public function isJeu(): ?bool
    {
        return $this->jeu;
    }

    public function setJeu(bool $jeu): static
    {
        $this->jeu = $jeu;

        return $this;
    }
}
