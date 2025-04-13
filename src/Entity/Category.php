<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;


#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['category:read'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::GUID)]
    #[Groups(['category:read'])]
    private ?string $uuid = null;

    #[ORM\Column(length: 64)]
    #[Groups(['category:read'])]
    private ?string $title = null;

    #[ORM\Column(length: 32)]
    #[Groups(['category:read'])]
    private ?string $icon = null;

    #[ORM\Column]
    #[Groups(['category:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['category:read'])]
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * @var Collection<int, MenuCategory>
     */
    #[ORM\OneToMany(targetEntity: MenuCategory::class, mappedBy: 'category', orphanRemoval: true)]
    private Collection $menuCategories;

    /**
     * @var Collection<int, FoodCategory>
     */
    #[ORM\OneToMany(targetEntity: FoodCategory::class, mappedBy: 'caterogy', orphanRemoval: true)]
    private Collection $foodCategories;

    public function __construct()
    {
        $this->menuCategories = new ArrayCollection();
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

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): static
    {
        $this->icon = $icon;

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
     * @return Collection<int, MenuCategory>
     */
    public function getMenuCategories(): Collection
    {
        return $this->menuCategories;
    }

    public function addMenuCategory(MenuCategory $menuCategory): static
    {
        if (!$this->menuCategories->contains($menuCategory)) {
            $this->menuCategories->add($menuCategory);
            $menuCategory->setCategory($this);
        }

        return $this;
    }

    public function removeMenuCategory(MenuCategory $menuCategory): static
    {
        if ($this->menuCategories->removeElement($menuCategory)) {
            // set the owning side to null (unless already changed)
            if ($menuCategory->getCategory() === $this) {
                $menuCategory->setCategory(null);
            }
        }

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
            $foodCategory->setCaterogy($this);
        }

        return $this;
    }

    public function removeFoodCategory(FoodCategory $foodCategory): static
    {
        if ($this->foodCategories->removeElement($foodCategory)) {
            // set the owning side to null (unless already changed)
            if ($foodCategory->getCaterogy() === $this) {
                $foodCategory->setCaterogy(null);
            }
        }

        return $this;
    }
}
