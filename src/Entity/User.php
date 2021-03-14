<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @UniqueEntity("email")
 */
class User implements UserInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     * @Assert\Email
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $gender;

    /**
     * @ORM\Column(type="date")
     * @Assert\LessThanOrEqual(
     *  value = "-18 years",
     *  message = "Vous ne disposez pas de l'age requis pour l'inscription."
     * )
     */
    private $dateOfBirth;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     */
    private $firstname;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     */
    private $lastname;

    /**
     * @ORM\Column(type="date")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $workflowState;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $profilImage;

    /**
     * @ORM\Column(type="datetime")
     */
    private $modifiedAt;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     */
    private $pseudonym;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Research", mappedBy="user")
     */
    private $researches;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Discussions", mappedBy="createdBy")
     */
    private $discussions;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Offers", mappedBy="createdBy")
     */
    private $offers;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Message", mappedBy="createdBy")
     */
    private $messages;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Favorites", mappedBy="user")
     */
    private $favorites;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\SignaledOffers", mappedBy="createdBy")
     */
    private $signaledOffers;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Associations", mappedBy="createdBy")
     */
    private $associations;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $token;

    /**
     * @ORM\OneToMany(targetEntity=SignaledDiscussions::class, mappedBy="createdBy")
     */
    private $signaledDiscussions;

    public function __construct()
    {
        $this->offers = new ArrayCollection();
        $this->researches = new ArrayCollection();
        $this->discussions = new ArrayCollection();
        $this->discussionsUser = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->signaledOffers = new ArrayCollection();
        $this->associations = new ArrayCollection();
        $this->signaledDiscussions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(string $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    public function getAge()
    {
        $now = new \DateTime('now');
        $age = $this->getDateOfBirth();
        $difference = $now->diff($age);

        return $difference->format('%y ans');
    }

    public function getDateOfBirth(): ?\DateTimeInterface
    {
        return $this->dateOfBirth;
    }

    public function setDateOfBirth(\DateTimeInterface $dateOfBirth): self
    {
        $this->dateOfBirth = $dateOfBirth;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

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

    public function getProfilImage(): ?string
    {
        return $this->profilImage;
    }

    public function setProfilImage(?string $profilImage): self
    {
        $this->profilImage = $profilImage;

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

    public function getPseudonym(): ?string
    {
        return $this->pseudonym;
    }

    public function setPseudonym(string $pseudonym): self
    {
        $this->pseudonym = $pseudonym;

        return $this;
    }

    /**
     * @return Collection|Research[]
     */
    public function getResearches(): Collection
    {
        return $this->researches;
    }

    public function addResearch(Research $research): self
    {
        if (!$this->researches->contains($research)) {
            $this->researches[] = $research;
            $research->setUser($this);
        }

        return $this;
    }

    public function removeResearch(Research $research): self
    {
        if ($this->researches->contains($research)) {
            $this->researches->removeElement($research);
            // set the owning side to null (unless already changed)
            if ($research->getUser() === $this) {
                $research->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Discussions[]
     */
    public function getDiscussions(): Collection
    {
        return $this->discussions;
    }

    public function addDiscussion(Discussions $discussion): self
    {
        if (!$this->discussions->contains($discussion)) {
            $this->discussions[] = $discussion;
            $discussion->setCreatedBy($this);
        }

        return $this;
    }

    public function removeDiscussion(Discussions $discussion): self
    {
        if ($this->discussions->contains($discussion)) {
            $this->discussions->removeElement($discussion);
            // set the owning side to null (unless already changed)
            if ($discussion->getCreatedBy() === $this) {
                $discussion->setCreatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Offers[]
     */
    public function getOffers(): Collection
    {
        return $this->offers;
    }

    public function addOffer(Offers $offer): self
    {
        if (!$this->offers->contains($offer)) {
            $this->offers[] = $offer;
            $offer->setCreatedBy($this);
        }

        return $this;
    }

    public function removeOffer(Offers $offer): self
    {
        if ($this->offers->contains($offer)) {
            $this->offers->removeElement($offer);
            // set the owning side to null (unless already changed)
            if ($offer->getCreatedBy() === $this) {
                $offer->setCreatedBy(null);
                $offer->setWorkflowState('deleted');
            }
        }

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
            $message->setCreatedBy($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): self
    {
        if ($this->messages->contains($message)) {
            $this->messages->removeElement($message);
            // set the owning side to null (unless already changed)
            if ($message->getCreatedBy() === $this) {
                $message->setCreatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Favorites[]
     */
    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    public function addFavorite(Favorites $favorite): self
    {
        if (!$this->favorites->contains($favorite)) {
            $this->favorites[] = $favorite;
            $favorite->setUser($this);
        }

        return $this;
    }

    public function removeFavorite(Favorites $favorite): self
    {
        if ($this->favorites->contains($favorite)) {
            $this->favorites->removeElement($favorite);
            // set the owning side to null (unless already changed)
            if ($favorite->getUser() === $this) {
                $favorite->setUser(null);
            }
        }

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
            $signaledOffer->setCreatedBy($this);
        }

        return $this;
    }

    public function removeSignaledOffer(SignaledOffers $signaledOffer): self
    {
        if ($this->signaledOffers->contains($signaledOffer)) {
            $this->signaledOffers->removeElement($signaledOffer);
            // set the owning side to null (unless already changed)
            if ($signaledOffer->getCreatedBy() === $this) {
                $signaledOffer->setCreatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Associations[]
     */
    public function getAssociations(): Collection
    {
        return $this->associations;
    }

    public function addAssociation(Associations $association): self
    {
        if (!$this->associations->contains($association)) {
            $this->associations[] = $association;
            $association->setCreatedBy($this);
        }

        return $this;
    }

    public function removeAssociation(Associations $association): self
    {
        if ($this->associations->contains($association)) {
            $this->associations->removeElement($association);
            // set the owning side to null (unless already changed)
            if ($association->getCreatedBy() === $this) {
                $association->setCreatedBy(null);
                $association->setWorkflowState('deleted');
                $association->setModifiedAt(new \DateTime());
            }
        }

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): self
    {
        $this->token = $token;

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
            $signaledDiscussion->setCreatedBy($this);
        }

        return $this;
    }

    public function removeSignaledDiscussion(SignaledDiscussions $signaledDiscussion): self
    {
        if ($this->signaledDiscussions->removeElement($signaledDiscussion)) {
            // set the owning side to null (unless already changed)
            if ($signaledDiscussion->getCreatedBy() === $this) {
                $signaledDiscussion->setCreatedBy(null);
            }
        }

        return $this;
    }
}
