<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DiscussionsRepository")
 */
class Discussions
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="discussions")
     * @ORM\JoinColumn(nullable=true)
     */
    private $createdBy;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Message", mappedBy="discussion")
     */
    private $messages;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Offers", inversedBy="discussions")
     * @ORM\JoinColumn(nullable=true)
     */
    private $offer;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $modifiedAt;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $workflowState;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isSignaled;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isDeletedCreator;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isDeletedUser;

    /**
     * @ORM\OneToMany(targetEntity=SignaledDiscussions::class, mappedBy="discussion")
     */
    private $signaledDiscussions;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
        $this->signaledDiscussions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * @return Collection|Message[]
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages[] = $message;
            $message->setDiscussion($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): self
    {
        if ($this->messages->contains($message)) {
            $this->messages->removeElement($message);
            // set the owning side to null (unless already changed)
            if ($message->getDiscussion() === $this) {
                $message->setDiscussion(null);
            }
        }

        return $this;
    }

    public function getOffer(): ?Offers
    {
        return $this->offer;
    }

    public function setOffer(?Offers $offer): self
    {
        $this->offer = $offer;

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

    public function getModifiedAt(): ?\DateTimeInterface
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(\DateTimeInterface $modifiedAt): self
    {
        $this->modifiedAt = $modifiedAt;

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

    public function getIsSignaled(): ?bool
    {
        return $this->isSignaled;
    }

    public function setIsSignaled(?bool $isSignaled): self
    {
        $this->isSignaled = $isSignaled;

        return $this;
    }

    public function getIsDeletedCreator(): ?bool
    {
        return $this->isDeletedCreator;
    }

    public function setIsDeletedCreator(?bool $isDeletedCreator): self
    {
        $this->isDeletedCreator = $isDeletedCreator;

        return $this;
    }

    public function getIsDeletedUser(): ?bool
    {
        return $this->isDeletedUser;
    }

    public function setIsDeletedUser(?bool $isDeletedUser): self
    {
        $this->isDeletedUser = $isDeletedUser;

        return $this;
    }

    /**
     * @return Collection|SignaledDiscussions[]
     */
    public function getSignaledDiscussions(): Collection
    {
        return $this->signaledDiscussions;
    }

    public function addSignaledDiscussion(SignaledDiscussions $signaledDiscussion): self
    {
        if (!$this->signaledDiscussions->contains($signaledDiscussion)) {
            $this->signaledDiscussions[] = $signaledDiscussion;
            $signaledDiscussion->setDiscussion($this);
        }

        return $this;
    }

    public function removeSignaledDiscussion(SignaledDiscussions $signaledDiscussion): self
    {
        if ($this->signaledDiscussions->removeElement($signaledDiscussion)) {
            // set the owning side to null (unless already changed)
            if ($signaledDiscussion->getDiscussion() === $this) {
                $signaledDiscussion->setDiscussion(null);
            }
        }

        return $this;
    }
}
