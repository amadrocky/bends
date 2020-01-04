<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\OffersRepository")
 */
class Offers
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $category;

    /**
     * @ORM\Column(type="integer")
     */
    private $createdBy;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $firstImage;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $secondImage;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $thirdImage;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $workflowState;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getCreatedBy(): ?int
    {
        return $this->createdBy;
    }

    public function setCreatedBy(int $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getFirstImage(): ?string
    {
        return $this->firstImage;
    }

    public function setFirstImage(?string $firstImage): self
    {
        $this->firstImage = $firstImage;

        return $this;
    }

    public function getSecondImage(): ?string
    {
        return $this->secondImage;
    }

    public function setSecondImage(?string $secondImage): self
    {
        $this->secondImage = $secondImage;

        return $this;
    }

    public function getThirdImage(): ?string
    {
        return $this->thirdImage;
    }

    public function setThirdImage(?string $thirdImage): self
    {
        $this->thirdImage = $thirdImage;

        return $this;
    }

    public function getWorkflowState(): ?string
    {
        return $this->workflowState;
    }

    public function setWorkflowState(string $workflowState): self
    {
        $this->workflowState = $workflowState;

        return $this;
    }
}
