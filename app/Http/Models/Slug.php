<?php

namespace App\Http\Models;

use App\Http\Models\User;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping AS ORM;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use LaravelDoctrine\ORM\Contracts\UrlRoutable;

/**
 * @ORM\Entity()
 * @ORM\Table(name="slug")
 * @ORM\HasLifecycleCallbacks()
 */
class Slug implements UrlRoutable
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", nullable=false)
     */
    public $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false, unique=true)
     */
    public $name;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false, unique=true)
     */
    public $slug;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=false)
     */
    public $isCanonical;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=false)
     */
    public $isActive;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=false)
     */
    public $isDeleted;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=false)
     */
    public $createdAt;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    public $updatedAt;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    public $deletedAt;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="User", inversedBy="slugs")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id", nullable=false)
     */
    public $createdBy;

    /**
     * @var ArrayCollection|PersonaSlug[]
     * @ORM\OneToMany(targetEntity="PersonaSlug", mappedBy="slug")
     */
    public $personasSlug;

    /**
     * Slug constructor.
     */
    public function __construct()
    {
        $this->personasSlug = new ArrayCollection();
        $this->isCanonical = (int) false;
    }

    /**
     * @ORM\PrePersist()
     */
    public function onPrePersist()
    {
        $this->createdAt = new DateTime();
        $this->createdBy = auth()->user();
        $this->isActive = (int) true;
        $this->isDeleted = (int) false;
    }

    /**
     * @param PreUpdateEventArgs $eventArgs
     * @ORM\PreUpdate()
     */
    public function onPreUpdate(PreUpdateEventArgs $eventArgs)
    {
        if (!empty($eventArgs->getEntityChangeSet())) {
            $this->updatedAt = new DateTime();
        }
    }

    /**
     *
     * @return string
     */
    public static function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getPersona()
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('isActive', true));

        return $this->personasSlug->matching($criteria)->first()
            ? $this->personasSlug->matching($criteria)->first()->getPersona()
            : false;
    }
}
