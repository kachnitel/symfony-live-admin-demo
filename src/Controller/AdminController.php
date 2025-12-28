<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Bicycle;
use App\Entity\Part;
use App\Entity\User;
use Kachnitel\AdminBundle\Security\AdminEntityVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminController extends AbstractController
{
    #[Route('/', name: 'admin_home')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig', [
            'entities' => [
                ['name' => 'User', 'class' => User::class, 'route' => 'admin_entity'],
                ['name' => 'Bicycle', 'class' => Bicycle::class, 'route' => 'admin_entity'],
                ['name' => 'Part', 'class' => Part::class, 'route' => 'admin_entity'],
            ],
        ]);
    }

    #[Route('/custom-admin/{entity}', name: 'admin_entity')]
    public function entity(string $entity): Response
    {
        // Map entity names to classes
        $entityMap = [
            'user' => User::class,
            'bicycle' => Bicycle::class,
            'part' => Part::class,
        ];

        $entityClass = $entityMap[strtolower($entity)] ?? null;
        if (!$entityClass) {
            throw $this->createNotFoundException('Entity not found');
        }

        // Get short class name
        $reflection = new \ReflectionClass($entityClass);
        $entityShortClass = $reflection->getShortName();

        // Enforce entity-level permissions using the bundle's voter
        // This demonstrates how custom controllers can leverage AdminEntityVoter
        // to respect #[Admin(permissions: [...])] even when required_role is null
        $this->denyAccessUnlessGranted(AdminEntityVoter::ADMIN_INDEX, $entityShortClass);

        return $this->render('admin/entity.html.twig', [
            'entityClass' => $entityClass,
            'entityShortClass' => $entityShortClass,
        ]);
    }
}
