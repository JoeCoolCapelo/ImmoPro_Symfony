<?php

namespace App\Command;

use App\Entity\Bien;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:seed-biens')]
class SeedBiensCommand extends Command
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $agent = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'agent@immopro.gn']);
        $owner = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'proprietaire@immopro.gn']);

        if (!$agent || !$owner) {
            $output->writeln("Agent or Owner not found. Run app:create-admin first.");
            return Command::FAILURE;
        }

        $biens = [
            [
                'titre' => 'Villa Moderne avec Piscine',
                'description' => 'Magnifique villa contemporaine située dans un quartier calme. 4 chambres, salon spacieux et cuisine équipée.',
                'prix' => 2500000000,
                'adresse' => 'Quartier Camayenne',
                'ville' => 'Conakry',
                'type' => 'villa',
                'nature' => 'vente',
                'surface' => 450,
                'nbPieces' => 6,
                'statut' => 'publié',
                'latitude' => 9.5370,
                'longitude' => -13.6773,
            ],
            [
                'titre' => 'Appartement Haut Standing',
                'description' => 'Appartement luxueux au 5ème étage avec vue sur mer. Sécurité 24h/24, ascenseur et parking.',
                'prix' => 15000000,
                'adresse' => 'Kaloum Centre',
                'ville' => 'Conakry',
                'type' => 'appartement',
                'nature' => 'location',
                'surface' => 120,
                'nbPieces' => 3,
                'statut' => 'publié',
                'latitude' => 9.5092,
                'longitude' => -13.7122,
            ],
            [
                'titre' => 'Terrain Constructible 500m²',
                'description' => 'Beau terrain plat prêt pour construction. Proche de la route principale et des commodités.',
                'prix' => 800000000,
                'adresse' => 'Kipé',
                'ville' => 'Conakry',
                'type' => 'terrain',
                'nature' => 'vente',
                'surface' => 500,
                'nbPieces' => 0,
                'statut' => 'publié',
                'latitude' => 9.6178,
                'longitude' => -13.6212,
            ],
        ];

        foreach ($biens as $data) {
            $bien = new Bien();
            $bien->setTitre($data['titre']);
            $bien->setDescription($data['description']);
            $bien->setPrix($data['prix']);
            $bien->setAdresse($data['adresse']);
            $bien->setVille($data['ville']);
            $bien->setType($data['type']);
            $bien->setNature($data['nature']);
            $bien->setSurface($data['surface']);
            $bien->setNbPieces($data['nbPieces']);
            $bien->setStatut($data['statut']);
            $bien->setLatitude($data['latitude']);
            $bien->setLongitude($data['longitude']);
            $bien->setAgent($agent);
            $bien->setOwner($owner);

            $this->entityManager->persist($bien);
            $output->writeln("Bien created: {$data['titre']}");
        }

        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
