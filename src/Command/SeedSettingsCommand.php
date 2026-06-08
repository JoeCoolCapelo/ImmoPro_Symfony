<?php

namespace App\Command;

use App\Entity\Setting;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:seed-settings')]
class SeedSettingsCommand extends Command
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $settings = [
            'agency_name' => 'ImmoPro Premium',
            'agency_address' => 'Avenue de la République, Conakry',
            'agency_phone' => '+224 620 00 00 00',
            'agency_email' => 'contact@immopro-premium.gn',
            'agency_logo' => 'https://via.placeholder.com/150',
            'currency' => 'GNF',
            'tax_rate' => '18',
        ];

        foreach ($settings as $key => $value) {
            $setting = $this->entityManager->getRepository(Setting::class)->findOneBy(['key' => $key]);
            if (!$setting) {
                $setting = new Setting();
                $setting->setKey($key);
                $setting->setValue($value);
                $this->entityManager->persist($setting);
                $output->writeln("Setting created: $key");
            }
        }

        $this->entityManager->flush();
        return Command::SUCCESS;
    }
}
