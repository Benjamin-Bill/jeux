<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Jeux
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $nom;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(type: 'float')]
    private float $prix;

    #[ORM\Column(type: 'integer')]
    private int $ponderation = 1;

    /**
     * @var Collection<int, User>
     */

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'jeuxes')]
    #[ORM\JoinTable(name: 'jeux_users')]
    #[ORM\JoinColumn(name: 'jeux_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'user_id', referencedColumnName: 'discord_id')]
    private Collection $Users;

    #[ORM\Column]
    private ?int $Min_player = null;

    #[ORM\Column(nullable: true)]
    private ?int $Max_player = null;

    /**
     * @var Collection<int, Valide>
     */
    #[ORM\OneToMany(targetEntity: Valide::class, mappedBy: 'jeu')]
    private Collection $valides;


    public function __construct()
    {
        $this->Users = new ArrayCollection();
        $this->valides = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function getPrix(): float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): self
    {
        $this->prix = $prix;
        return $this;
    }

    public function getPonderation(): int
    {
        return $this->ponderation;
    }

    public function setPonderation(int $ponderation): self
    {
        $this->ponderation = $ponderation;
        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->Users;
    }

    public function addUser(User $user): static
    {
        if (!$this->Users->contains($user)) {
            $this->Users->add($user);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        $this->Users->removeElement($user);

        return $this;
    }

    public function getMinPlayer(): ?int
    {
        return $this->Min_player;
    }

    public function setMinPlayer(int $Min_player): static
    {
        $this->Min_player = $Min_player;

        return $this;
    }

    public function getMaxPlayer(): ?int
    {
        return $this->Max_player;
    }

    public function setMaxPlayer(?int $Max_player): static
    {
        $this->Max_player = $Max_player;

        return $this;
    }

    /**
     * @return Collection<int, Valide>
     */
    public function getValides(): Collection
    {
        return $this->valides;
    }

    public function addValide(Valide $valide): static
    {
        if (!$this->valides->contains($valide)) {
            $this->valides->add($valide);
            $valide->setJeu($this);
        }

        return $this;
    }

    public function removeValide(Valide $valide): static
    {
        if ($this->valides->removeElement($valide)) {
            // set the owning side to null (unless already changed)
            if ($valide->getJeu() === $this) {
                $valide->setJeu(null);
            }
        }

        return $this;
    }



}