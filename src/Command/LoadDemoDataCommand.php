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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:load-demo-data',
    description: 'Load demo data into the database',
)]
class LoadDemoDataCommand extends Command
{
    private const FIRST_NAMES = [
        'John', 'Jane', 'Bob', 'Alice', 'Charlie', 'Diana', 'Edward', 'Fiona',
        'George', 'Hannah', 'Ivan', 'Julia', 'Kevin', 'Laura', 'Michael', 'Nina',
    ];

    private const LAST_NAMES = [
        'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis',
        'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Taylor',
    ];

    private const BIKE_BRANDS = [
        'Trek' => ['Domane SL 5', 'Madone SLR', 'Emonda SL 6', 'Checkpoint SL 5'],
        'Specialized' => ['Tarmac SL7', 'Roubaix Sport', 'Diverge', 'Allez Sprint'],
        'Cannondale' => ['SuperSix EVO', 'Synapse Carbon', 'CAAD13', 'Topstone Carbon'],
        'Giant' => ['TCR Advanced', 'Defy Advanced', 'Propel Advanced', 'Revolt Advanced'],
        'Pinarello' => ['Dogma F', 'Prince', 'Paris', 'Gan'],
        'Cervelo' => ['R5', 'S5', 'Caledonia', 'Aspero'],
    ];

    private const COLORS = ['Blue', 'Red', 'Black', 'White', 'Green', 'Yellow', 'Orange', 'Silver', 'Carbon'];

    private const PART_TYPES = [
        'Fork' => ['Carbon Fork', 'Alloy Fork', 'Suspension Fork', 'Aero Fork'],
        'Wheelset' => ['Road Wheelset', 'Aero Wheelset', 'Climbing Wheelset', 'Training Wheelset'],
        'Frame' => ['Carbon Frame', 'Alloy Frame', 'Steel Frame', 'Titanium Frame'],
        'Handlebar' => ['Drop Bar', 'Aero Bar', 'Compact Bar', 'Gravel Bar'],
        'Saddle' => ['Racing Saddle', 'Comfort Saddle', 'Endurance Saddle', 'TT Saddle'],
        'Pedals' => ['Clipless Pedals', 'Platform Pedals', 'SPD Pedals', 'Look Pedals'],
        'Brakes' => ['Disc Brakes', 'Rim Brakes', 'Hydraulic Brakes', 'Mechanical Brakes'],
        'Groupset' => ['105 Groupset', 'Ultegra Groupset', 'Dura-Ace Groupset', 'Force Groupset'],
    ];

    private const MANUFACTURERS = [
        'Shimano', 'SRAM', 'Campagnolo', 'FSA', 'Fizik', 'Brooks', 'Continental',
        'Mavic', 'DT Swiss', 'Zipp', 'Enve', 'Chris King', 'Hope', 'Bontrager', 'Roval',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('purge', null, InputOption::VALUE_NONE, 'Purge existing data before loading')
            ->addOption('users', null, InputOption::VALUE_REQUIRED, 'Number of users to create', '10')
            ->addOption('bicycles', null, InputOption::VALUE_REQUIRED, 'Number of bicycles to create', '5')
            ->addOption('parts', null, InputOption::VALUE_REQUIRED, 'Number of standalone parts to create', '20');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $purge = (bool) $input->getOption('purge');
        $userCount = (int) $input->getOption('users');
        $bicycleCount = (int) $input->getOption('bicycles');
        $partCount = (int) $input->getOption('parts');

        if ($purge) {
            $io->section('Purging existing data...');
            $this->purgeData($io);
        }

        $io->section('Loading demo data...');

        // Create users
        $users = $this->createUsers($userCount);
        $io->text(sprintf('Created %d users', count($users)));

        // Create bicycles with parts
        $bicycles = $this->createBicycles($bicycleCount);
        $io->text(sprintf('Created %d bicycles with attached parts', count($bicycles)));

        // Create standalone parts
        $standaloneParts = $this->createStandaloneParts($partCount);
        $io->text(sprintf('Created %d standalone parts', count($standaloneParts)));

        $this->entityManager->flush();

        $io->success('Demo data loaded successfully!');
        $io->table(
            ['Entity', 'Count'],
            [
                ['Users', count($users)],
                ['Bicycles', count($bicycles)],
                ['Parts (total)', $this->countTotalParts($bicycles) + count($standaloneParts)],
            ]
        );

        $io->note('Demo user: user@example.com / password');

        return Command::SUCCESS;
    }

    private function purgeData(SymfonyStyle $io): void
    {
        $connection = $this->entityManager->getConnection();

        // Disable foreign key checks for SQLite
        $connection->executeStatement('PRAGMA foreign_keys = OFF');

        // Delete in correct order to respect foreign keys
        $connection->executeStatement('DELETE FROM parts');
        $io->text('Purged parts table');

        $connection->executeStatement('DELETE FROM bicycles');
        $io->text('Purged bicycles table');

        $connection->executeStatement('DELETE FROM users');
        $io->text('Purged users table');

        // Re-enable foreign key checks
        $connection->executeStatement('PRAGMA foreign_keys = ON');
    }

    /**
     * @return array<User>
     */
    private function createUsers(int $count): array
    {
        $users = [];

        // Always create the demo user first
        $demoUser = new User();
        $demoUser->setName('Demo User')
            ->setEmail('user@example.com')
            ->setActive(true)
            ->setCreatedAt($this->randomDate(365))
            ->setLastLoginAt($this->randomDate(7));

        $hashedPassword = $this->passwordHasher->hashPassword($demoUser, 'password');
        $demoUser->setPassword($hashedPassword);
        $this->entityManager->persist($demoUser);
        $users[] = $demoUser;

        // Create additional random users
        $usedEmails = ['user@example.com'];
        for ($i = 1; $i < $count; $i++) {
            $firstName = self::FIRST_NAMES[array_rand(self::FIRST_NAMES)];
            $lastName = self::LAST_NAMES[array_rand(self::LAST_NAMES)];
            $name = $firstName . ' ' . $lastName;

            // Generate unique email
            $baseEmail = strtolower($firstName) . '.' . strtolower($lastName) . '@example.com';
            $email = $baseEmail;
            $counter = 1;
            while (in_array($email, $usedEmails, true)) {
                $email = strtolower($firstName) . '.' . strtolower($lastName) . $counter . '@example.com';
                $counter++;
            }
            $usedEmails[] = $email;

            $user = new User();
            $user->setName($name)
                ->setEmail($email)
                ->setActive(random_int(0, 100) > 20) // 80% active
                ->setCreatedAt($this->randomDate(365));

            // 80% have logged in at some point
            if (random_int(0, 100) > 20) {
                $user->setLastLoginAt($this->randomDate(30));
            }

            $hashedPassword = $this->passwordHasher->hashPassword($user, 'password');
            $user->setPassword($hashedPassword);

            $this->entityManager->persist($user);
            $users[] = $user;
        }

        return $users;
    }

    /**
     * @return array<Bicycle>
     */
    private function createBicycles(int $count): array
    {
        $bicycles = [];
        $brands = array_keys(self::BIKE_BRANDS);

        for ($i = 0; $i < $count; $i++) {
            $brand = $brands[array_rand($brands)];
            $models = self::BIKE_BRANDS[$brand];
            $model = $models[array_rand($models)];

            $bicycle = new Bicycle();
            $bicycle->setBrand($brand)
                ->setModel($model)
                ->setColor(self::COLORS[array_rand(self::COLORS)])
                ->setYear(random_int(2020, 2026))
                ->setCreatedAt($this->randomDate(365));

            // Add 2-5 parts to each bicycle
            $partCount = random_int(2, 5);
            $usedTypes = [];
            for ($j = 0; $j < $partCount; $j++) {
                $partTypes = array_keys(self::PART_TYPES);
                $partType = $partTypes[array_rand($partTypes)];

                // Avoid duplicate part types on same bike
                if (in_array($partType, $usedTypes, true)) {
                    continue;
                }
                $usedTypes[] = $partType;

                $partNames = self::PART_TYPES[$partType];
                $partName = $partNames[array_rand($partNames)];

                $part = new Part();
                $part->setName($partName)
                    ->setManufacturer(self::MANUFACTURERS[array_rand(self::MANUFACTURERS)])
                    ->setPrice((string) (random_int(50, 3000) + random_int(0, 99) / 100))
                    ->setCreatedAt($this->randomDate(365))
                    ->setBicycle($bicycle);

                $this->entityManager->persist($part);
            }

            $this->entityManager->persist($bicycle);
            $bicycles[] = $bicycle;
        }

        return $bicycles;
    }

    /**
     * @return array<Part>
     */
    private function createStandaloneParts(int $count): array
    {
        $parts = [];
        $partTypes = array_keys(self::PART_TYPES);

        for ($i = 0; $i < $count; $i++) {
            $partType = $partTypes[array_rand($partTypes)];
            $partNames = self::PART_TYPES[$partType];
            $partName = $partNames[array_rand($partNames)];

            $part = new Part();
            $part->setName($partName)
                ->setManufacturer(self::MANUFACTURERS[array_rand(self::MANUFACTURERS)])
                ->setPrice((string) (random_int(20, 500) + random_int(0, 99) / 100))
                ->setCreatedAt($this->randomDate(365));

            $this->entityManager->persist($part);
            $parts[] = $part;
        }

        return $parts;
    }

    /**
     * @param array<Bicycle> $bicycles
     */
    private function countTotalParts(array $bicycles): int
    {
        $count = 0;
        foreach ($bicycles as $bicycle) {
            $count += $bicycle->getParts()->count();
        }
        return $count;
    }

    /**
     * Generate a random date within the last $daysBack days.
     */
    private function randomDate(int $daysBack): \DateTimeImmutable
    {
        $timestamp = time() - random_int(0, $daysBack * 24 * 60 * 60);
        return new \DateTimeImmutable('@' . $timestamp);
    }
}
