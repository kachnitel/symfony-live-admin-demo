<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Part;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PartController extends AbstractController
{
    /**
     * Batch-detaches selected parts from their bicycle.
     *
     * Receives a POST with `ids[]` — the standard form submitted by the
     * bundle's route-based batch action handler.  Parts that are already
     * standalone (bicycle = null) are silently skipped; only parts that
     * currently have a bicycle assigned are counted in the flash message.
     */
    #[Route('/admin/part/batch-detach', name: 'app_part_batch_detach', methods: ['POST'])]
    public function batchDetach(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var array<string|int> $ids */
        $ids = $request->request->all('ids');

        if (empty($ids)) {
            $this->addFlash('warning', 'No parts selected.');

            return $this->redirectToRoute('app_admin_entity_index', ['entitySlug' => 'part']);
        }

        $count = 0;
        foreach ($ids as $id) {
            $part = $em->find(Part::class, (int) $id);
            if ($part !== null && $part->getBicycle() !== null) {
                $part->setBicycle(null);
                $count++;
            }
        }
        $em->flush();

        if ($count > 0) {
            $this->addFlash('success', sprintf('%d part(s) detached from their bicycle.', $count));
        } else {
            $this->addFlash('info', 'Selected parts were already standalone (no bicycle assigned).');
        }

        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_admin_entity_index', ['entitySlug' => 'part']);
    }
}
