<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kachnitel\AdminBundle\Attribute\Admin;

#[ORM\Entity]
#[ORM\Table(name: 'parts')]
#[Admin(
    icon: 'settings',
    enableBatchActions: true
)]
class Part
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $manufacturer = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $price = null;

    #[ORM\ManyToOne(targetEntity: Bicycle::class, inversedBy: 'parts')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Bicycle $bicycle = null;

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
}
