<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Concerts
 *
 * @ORM\Table(name="concerts", indexes={@ORM\Index(name="bandId", columns={"bandId"}), @ORM\Index(name="venueId", columns={"venueId"})})
 * @ORM\Entity
 */
class Concerts
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="date", type="string", length=11, nullable=false)
     */
    private $date;

    /**
     * @var \Venues
     *
     * @ORM\ManyToOne(targetEntity="Venues")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="venueId", referencedColumnName="id")
     * })
     */
    private $venueid;

    /**
     * @var \Bands
     *
     * @ORM\ManyToOne(targetEntity="Bands")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="bandId", referencedColumnName="id")
     * })
     */
    private $bandid;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function setDate(string $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getVenueid(): ?Venues
    {
        return $this->venueid;
    }

    public function setVenueid(?Venues $venueid): self
    {
        $this->venueid = $venueid;

        return $this;
    }

    public function getBandid(): ?Bands
    {
        return $this->bandid;
    }

    public function setBandid(?Bands $bandid): self
    {
        $this->bandid = $bandid;

        return $this;
    }

    


}
