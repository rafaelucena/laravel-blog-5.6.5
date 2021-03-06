<?php

namespace App\Http\Models;

use App\Http\Models\BlogCategory;
use App\Http\Models\Comment;
use App\Http\Models\User;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping AS ORM;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Illuminate\Support\Str;
use LaravelDoctrine\ORM\Contracts\UrlRoutable;

/**
 * @ORM\Entity()
 * @ORM\Table(name="blog")
 * @ORM\HasLifecycleCallbacks()
 */
class Blog implements UrlRoutable
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", nullable=false)
     */
    public $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=127, nullable=false)
     */
    public $title;

    /**
     * @var string
     * @ORM\Column(type="string", length=127, nullable=false)
     */
    public $slug;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    public $image;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=false)
     */
    public $excerpt;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=false)
     */
    public $description;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     */
    public $views;

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
     * @var integer
     * @ORM\Column(type="integer", nullable=false)
     */
    public $allowComments;

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
     * @ORM\ManyToOne(targetEntity="User", inversedBy="blogs")
     */
    public $user;

    /**
     * @var ArrayCollection|Comment[]
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="blog")
     */
    public $comments;

    /**
     * @var ArrayCollection|BlogCategory[]
     * @ORM\OneToMany(targetEntity="BlogCategory", mappedBy="blog")
     */
    public $blogCategories;

    public function __construct()
    {
        $this->blogCategories = new ArrayCollection();
    }

    /**
     * @ORM\PrePersist()
     */
    public function onPrePersist()
    {
        $this->slug = Str::slug($this->title);
        $this->createdAt = new DateTime();
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

    public function getCategories(bool $all = false)
    {
        $criteria = Criteria::create();
        if ($all !== true) {
            $criteria->where(Criteria::expr()->eq('isActive', true));
        }
        return $this->blogCategories->matching($criteria);
    }

    /**
     *
     * @return string
     */
    public static function getRouteKeyName(): string
    {
        return 'slug';
    }
}
