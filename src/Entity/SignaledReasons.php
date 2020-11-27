<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SignaledReasonsRepository")
 */
class SignaledReasons
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
    private $reason;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\SignaledOffers", mappedBy="reason")
     */
    private $signaledOffers;

    public function __construct()
    {
        $this->signaledOffers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): self
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * @return Collection|SignaledOffers[]
     */
    public function getSignaledOffers(): Collection
    {
        return $this->signaledOffers;
    }

    public function addSignaledOffer(SignaledOffers $signaledOffer): self
    {
        if (!$this->signaledOffers->contains($signaledOffer)) {
            $this->signaledOffers[] = $signaledOffer;
            $signaledOffer->setReason($this);
        }

        return $this;
    }

    public function removeSignaledOffer(SignaledOffers $signaledOffer): self
    {
        if ($this->signaledOffers->contains($signaledOffer)) {
            $this->signaledOffers->removeElement($signaledOffer);
            // set the owning side to null (unless already changed)
            if ($signaledOffer->getReason() === $this) {
                $signaledOffer->setReason(null);
            }
        }

        return $this;
    }
}
