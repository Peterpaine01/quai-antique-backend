<?php

namespace App\Entity;

use App\Repository\FoodRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;


#[ORM\Entity(repositoryClass: FoodRepository::class)]
class Food
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['food:read'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::GUID)]
    #[Groups(['food:read'])]
    private ?string $uuid = null;

    #[ORM\Column(length: 64)]
    #[Groups(['food:read'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['food:read'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['food:read'])]
    private ?float $price = null;

    #[ORM\Column]
    #[Groups(['food:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['food:read'])]
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * @var Collection<int, FoodCategory>
     */
    #[ORM\OneToMany(targetEntity: FoodCategory::class, mappedBy: 'food', orphanRemoval: true)]
    private Collection $foodCategories;

    public function __construct()
    {
        $this->foodCategories = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
{
    $this->price = $price;
    return $this;
}

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, FoodCategory>
     */
    public function getFoodCategories(): Collection
    {
        return $this->foodCategories;
    }

    public function addFoodCategory(FoodCategory $foodCategory): static
    {
        if (!$this->foodCategories->contains($foodCategory)) {
            $this->foodCategories->add($foodCategory);
            $foodCategory->setFood($this);
        }

        return $this;
    }

    public function removeFoodCategory(FoodCategory $foodCategory): static
    {
        if ($this->foodCategories->removeElement($foodCategory)) {
            // set the owning side to null (unless already changed)
            if ($foodCategory->getFood() === $this) {
                $foodCategory->setFood(null);
            }
        }

        return $this;
    }
}
