<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Bicycle;
use App\Entity\Part;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BicycleController extends AbstractController
{
    /**
     * Duplicates a bicycle and all its parts, then redirects to the new record.
     */
    #[Route('/admin/bicycle/{id}/duplicate', name: 'app_bicycle_duplicate', methods: ['GET'])]
    public function duplicate(Bicycle $bicycle, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $copy = new Bicycle();
        $copy->setBrand($bicycle->getBrand());
        $copy->setModel($bicycle->getModel() . ' (copy)');
        $copy->setColor($bicycle->getColor());
        $copy->setYear($bicycle->getYear());
        $copy->setCreatedAt(new \DateTimeImmutable());

        foreach ($bicycle->getParts() as $part) {
            $partCopy = new Part();
            $partCopy->setName($part->getName());
            $partCopy->setManufacturer($part->getManufacturer());
            $partCopy->setPrice($part->getPrice());
            $partCopy->setCreatedAt(new \DateTimeImmutable());
            $partCopy->setBicycle($copy);
            $em->persist($partCopy);
        }

        $em->persist($copy);
        $em->flush();

        $this->addFlash('success', sprintf(
            'Bicycle "%s %s" duplicated successfully.',
            $copy->getBrand(),
            $copy->getModel(),
        ));

        return $this->redirectToRoute('app_admin_entity_show', [
            'entitySlug' => 'bicycle',
            'id' => (int) $copy->getId(),
        ]);
    }
}
