<?php

namespace App\Entity;

use App\Repository\SignaledDiscussionsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SignaledDiscussionsRepository::class)
 */
class SignaledDiscussions
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Discussions::class, inversedBy="signaledDiscussions")
     */
    private $discussion;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="signaledDiscussions")
     */
    private $createdBy;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $workflowState;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDiscussion(): ?Discussions
    {
        return $this->discussion;
    }

    public function setDiscussion(?Discussions $discussion): self
    {
        $this->discussion = $discussion;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
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
