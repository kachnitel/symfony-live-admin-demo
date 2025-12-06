<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Bicycle;
use App\Entity\Part;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:load-demo-data',
    description: 'Load demo data into the database',
)]
class LoadDemoDataCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Create users
        $users = [
            ['name' => 'John Doe', 'email' => 'john@example.com', 'active' => true],
            ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'active' => true],
            ['name' => 'Bob Johnson', 'email' => 'bob@example.com', 'active' => false],
        ];

        foreach ($users as $userData) {
            $user = new User();
            $user->setName($userData['name'])
                ->setEmail($userData['email'])
                ->setActive($userData['active']);
            $this->entityManager->persist($user);
        }

        // Create bicycles with parts
        $bicycle1 = new Bicycle();
        $bicycle1->setBrand('Trek')
            ->setModel('Domane SL 5')
            ->setColor('Blue')
            ->setYear(2023);

        $parts1 = [
            ['name' => 'Carbon Fork', 'manufacturer' => 'Trek', 'price' => '299.99'],
            ['name' => 'Road Wheelset', 'manufacturer' => 'Bontrager', 'price' => '599.99'],
            ['name' => 'Carbon Frame', 'manufacturer' => 'Trek', 'price' => '1499.99'],
            ['name' => 'Ergonomic Grips', 'manufacturer' => 'Bontrager', 'price' => '29.99'],
        ];

        foreach ($parts1 as $partData) {
            $part = new Part();
            $part->setName($partData['name'])
                ->setManufacturer($partData['manufacturer'])
                ->setPrice($partData['price'])
                ->setBicycle($bicycle1);
            $this->entityManager->persist($part);
        }

        $this->entityManager->persist($bicycle1);

        // Create second bicycle
        $bicycle2 = new Bicycle();
        $bicycle2->setBrand('Specialized')
            ->setModel('Tarmac SL7')
            ->setColor('Red')
            ->setYear(2024);

        $parts2 = [
            ['name' => 'Carbon Fork', 'manufacturer' => 'Specialized', 'price' => '349.99'],
            ['name' => 'Aero Wheelset', 'manufacturer' => 'Roval', 'price' => '1299.99'],
            ['name' => 'S-Works Frame', 'manufacturer' => 'Specialized', 'price' => '2999.99'],
            ['name' => 'Bar Tape', 'manufacturer' => 'Supacaz', 'price' => '34.99'],
        ];

        foreach ($parts2 as $partData) {
            $part = new Part();
            $part->setName($partData['name'])
                ->setManufacturer($partData['manufacturer'])
                ->setPrice($partData['price'])
                ->setBicycle($bicycle2);
            $this->entityManager->persist($part);
        }

        $this->entityManager->persist($bicycle2);

        // Create a third bicycle
        $bicycle3 = new Bicycle();
        $bicycle3->setBrand('Cannondale')
            ->setModel('SuperSix EVO')
            ->setColor('Green')
            ->setYear(2023);

        $parts3 = [
            ['name' => 'Carbon Fork', 'manufacturer' => 'Cannondale', 'price' => '279.99'],
            ['name' => 'Racing Wheels', 'manufacturer' => 'Mavic', 'price' => '799.99'],
            ['name' => 'SuperSix Frame', 'manufacturer' => 'Cannondale', 'price' => '1799.99'],
        ];

        foreach ($parts3 as $partData) {
            $part = new Part();
            $part->setName($partData['name'])
                ->setManufacturer($partData['manufacturer'])
                ->setPrice($partData['price'])
                ->setBicycle($bicycle3);
            $this->entityManager->persist($part);
        }

        $this->entityManager->persist($bicycle3);

        // Add some standalone parts (not attached to any bicycle)
        $standaloneParts = [
            ['name' => 'Saddle', 'manufacturer' => 'Fizik', 'price' => '149.99'],
            ['name' => 'Pedals', 'manufacturer' => 'Shimano', 'price' => '89.99'],
            ['name' => 'Chain', 'manufacturer' => 'KMC', 'price' => '24.99'],
        ];

        foreach ($standaloneParts as $partData) {
            $part = new Part();
            $part->setName($partData['name'])
                ->setManufacturer($partData['manufacturer'])
                ->setPrice($partData['price']);
            $this->entityManager->persist($part);
        }

        $this->entityManager->flush();

        $io->success('Demo data loaded successfully!');
        $io->table(
            ['Entity', 'Count'],
            [
                ['Users', count($users)],
                ['Bicycles', 3],
                ['Parts', count($parts1) + count($parts2) + count($parts3) + count($standaloneParts)],
            ]
        );

        return Command::SUCCESS;
    }
}
