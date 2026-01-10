<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Kachnitel\AdminBundle\Attribute\Admin;
use Kachnitel\AdminBundle\Attribute\ColumnFilter;

#[ORM\Entity]
#[ORM\Table(name: 'bicycles')]
#[Admin(
    label: 'Bike',
    icon: 'pedal_bike',
    enableBatchActions: true,
    itemsPerPage: 5,
    sortBy: 'year',
    sortDirection: 'DESC'
)]
class Bicycle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    private string $brand;

    #[ORM\Column(type: 'string', length: 100)]
    private string $model;

    #[ORM\Column(type: 'string', length: 50)]
    private string $color;

    #[ORM\Column(type: 'integer')]
    private int $year;

    #[ORM\Column(type: 'datetime_immutable')]
    #[ColumnFilter(type: 'daterange', label: 'Date Added', priority: 1)]
    private \DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, Part>
     */
    #[ORM\OneToMany(targetEntity: Part::class, mappedBy: 'bicycle', cascade: ['persist', 'remove'])]
    private Collection $parts;

    public function __construct()
    {
        $this->parts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBrand(): string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): self
    {
        $this->brand = $brand;
        return $this;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function setModel(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;
        return $this;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function setYear(int $year): self
    {
        $this->year = $year;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return Collection<int, Part>
     */
    public function getParts(): Collection
    {
        return $this->parts;
    }

    public function addPart(Part $part): self
    {
        if (!$this->parts->contains($part)) {
            $this->parts->add($part);
            $part->setBicycle($this);
        }

        return $this;
    }

    public function removePart(Part $part): self
    {
        if ($this->parts->removeElement($part)) {
            if ($part->getBicycle() === $this) {
                $part->setBicycle(null);
            }
        }

        return $this;
    }
}
