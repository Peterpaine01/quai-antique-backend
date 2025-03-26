<?php

namespace App\Entity;

use App\Repository\FoodCategoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FoodCategoryRepository::class)]
class FoodCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'foodCategories')]
    #[ORM\JoinColumn(nullable: false)]
    private ?food $food = null;

    #[ORM\ManyToOne(inversedBy: 'foodCategories')]
    #[ORM\JoinColumn(nullable: false)]
    private ?category $caterogy = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFood(): ?food
    {
        return $this->food;
    }

    public function setFood(?food $food): static
    {
        $this->food = $food;

        return $this;
    }

    public function getCaterogy(): ?category
    {
        return $this->caterogy;
    }

    public function setCaterogy(?category $caterogy): static
    {
        $this->caterogy = $caterogy;

        return $this;
    }
}
