<?php

namespace App\DataFixtures;

use App\Entity\Jour;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class JourFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $jours = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($jours as $jourNom) {
            $jour = new Jour();
            $jour->setJour($jourNom);
            $jour->setTirage(false);
            $jour->setJeu(false);

            $manager->persist($jour);
        }

        $manager->flush();
    }
}