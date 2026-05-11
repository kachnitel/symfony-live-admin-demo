<?php

declare(strict_types=1);

namespace App\Twig\Components\Batch;

use App\Entity\Bicycle;
use App\Entity\Part;
use Doctrine\ORM\EntityManagerInterface;
use Kachnitel\AdminBundle\Twig\Components\AdminAction\BatchActionTrait;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Batch action LiveComponent — assigns selected Parts to a chosen Bicycle.
 *
 * Rendered in the batch actions bar when parts are selected.  The user picks
 * a bicycle from the dropdown and clicks "Assign"; all selected parts are
 * then re-associated with that bicycle and the EntityList is refreshed.
 *
 * Receives selectedIds / entityClass / entityShortClass from the batch bar
 * via BatchActionTrait LiveProps and emits 'admin:action:completed' on success.
 */
#[AsLiveComponent('App:Batch:AssignToBike', template: 'components/Batch/AssignToBikeAction.html.twig')]
class AssignToBikeAction
{
    use DefaultActionTrait;
    use BatchActionTrait;

    /**
     * The bicycle ID selected in the dropdown.
     */
    #[LiveProp(writable: true)]
    public ?int $bicycleId = null;

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * Returns all bicycles sorted for the dropdown.
     *
     * @return array<Bicycle>
     */
    public function getBicycles(): array
    {
        /** @var array<Bicycle> $result */
        $result = $this->em->getRepository(Bicycle::class)
            ->createQueryBuilder('b')
            ->orderBy('b.brand', 'ASC')
            ->addOrderBy('b.model', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    #[LiveAction]
    public function execute(): void
    {
        if (empty($this->selectedIds) || $this->bicycleId === null) {
            return;
        }

        $bicycle = $this->em->find(Bicycle::class, $this->bicycleId);
        if ($bicycle === null) {
            return;
        }

        /** @var \Doctrine\ORM\EntityRepository<Part> $repo */
        $repo = $this->em->getRepository(Part::class);
        $affected = [];

        foreach ($this->selectedIds as $id) {
            $part = $repo->find($id);
            if ($part !== null) {
                $part->setBicycle($bicycle);
                $affected[] = $id;
            }
        }
        $this->em->flush();

        $this->bicycleId = null;

        $this->completeAction('assign-to-bike', $affected);
    }
}
