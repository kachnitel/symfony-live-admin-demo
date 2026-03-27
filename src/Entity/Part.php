<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kachnitel\AdminBundle\Attribute\Admin;
use Kachnitel\AdminBundle\Attribute\AdminColumn;
use Kachnitel\AdminBundle\Attribute\ColumnFilter;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'parts')]
#[Admin(
    icon: 'settings',
    enableBatchActions: true,
    enableColumnVisibility: true,
    enableInlineEdit: true,
    itemsPerPage: 15,
    sortBy: 'name',
    sortDirection: 'ASC',
    archiveExpression: 'item.archived',
    excludeColumns: ['archived'],
)]
class Part
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[ColumnFilter(placeholder: 'Part name...', priority: 1)]
    #[Assert\NotBlank(message: 'Part name must not be blank.')]
    #[Assert\Length(max: 100, maxMessage: 'Name cannot exceed {{ limit }} characters.')]
    private string $name;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[ColumnFilter(placeholder: 'Manufacturer...', priority: 2)]
    private ?string $manufacturer = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[ColumnFilter(type: 'number', label: 'Min Price', operator: '>=', placeholder: 'Min price', priority: 3)]
    #[Assert\PositiveOrZero(message: 'Price must be zero or positive.')]
    private ?string $price = null;

    #[ORM\ManyToOne(targetEntity: Bicycle::class, inversedBy: 'parts')]
    #[ORM\JoinColumn(nullable: true)]
    #[ColumnFilter(type: 'relation', searchFields: ['brand', 'model'], label: 'Bike', placeholder: 'Search bike...', priority: 5)]
    private ?Bicycle $bicycle = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[ColumnFilter(type: 'daterange', priority: 10)]
    #[AdminColumn(editable: false)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'boolean')]
    private bool $archived = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getManufacturer(): ?string
    {
        return $this->manufacturer;
    }

    public function setManufacturer(?string $manufacturer): self
    {
        $this->manufacturer = $manufacturer;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getBicycle(): ?Bicycle
    {
        return $this->bicycle;
    }

    public function setBicycle(?Bicycle $bicycle): self
    {
        $this->bicycle = $bicycle;
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

    public function isArchived(): bool
    {
        return $this->archived;
    }

    public function setArchived(bool $archived): self
    {
        $this->archived = $archived;
        return $this;
    }
}
