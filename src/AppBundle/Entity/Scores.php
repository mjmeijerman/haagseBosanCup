<?php

namespace AppBundle\Entity;

use AppBundle\AppBundle;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use AppBundle\Entity\Turnster;

/**
 * @ORM\Entity
 * @ORM\Table(name="scores")
 */
class Scores
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $wedstrijdnummer;

    /**
     * @ORM\OneToOne(targetEntity="Turnster", mappedBy="scores")
     * @var Turnster
     */
    private $turnster;

    /**
     * @ORM\Column(type="string", length=55, nullable=true)
     */
    private $wedstrijddag;

    /**
     * @ORM\Column(type="string", length=55, nullable=true)
     */
    private $wedstrijdronde;

    /**
     * @ORM\Column(type="string", length=55, nullable=true)
     */
    private $baan;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set wedstrijdnummer
     *
     * @param integer $wedstrijdnummer
     * @return Scores
     */
    public function setWedstrijdnummer($wedstrijdnummer)
    {
        $this->wedstrijdnummer = $wedstrijdnummer;

        return $this;
    }

    /**
     * Get wedstrijdnummer
     *
     * @return integer 
     */
    public function getWedstrijdnummer()
    {
        return $this->wedstrijdnummer;
    }

    /**
     * Set turnster
     *
     * @param \AppBundle\Entity\Turnster $turnster
     * @return Scores
     */
    public function setTurnster(\AppBundle\Entity\Turnster $turnster = null)
    {
        $this->turnster = $turnster;

        return $this;
    }

    /**
     * Get turnster
     *
     * @return \AppBundle\Entity\Turnster 
     */
    public function getTurnster()
    {
        return $this->turnster;
    }

    /**
     * Set wedstrijddag
     *
     * @param string $wedstrijddag
     * @return Scores
     */
    public function setWedstrijddag($wedstrijddag)
    {
        $this->wedstrijddag = $wedstrijddag;

        return $this;
    }

    /**
     * Get wedstrijddag
     *
     * @return string 
     */
    public function getWedstrijddag()
    {
        return $this->wedstrijddag;
    }

    /**
     * Set wedstrijdronde
     *
     * @param string $wedstrijdronde
     * @return Scores
     */
    public function setWedstrijdronde($wedstrijdronde)
    {
        $this->wedstrijdronde = $wedstrijdronde;

        return $this;
    }

    /**
     * Get wedstrijdronde
     *
     * @return string 
     */
    public function getWedstrijdronde()
    {
        return $this->wedstrijdronde;
    }

    /**
     * Set baan
     *
     * @param string $baan
     * @return Scores
     */
    public function setBaan($baan)
    {
        $this->baan = $baan;

        return $this;
    }

    /**
     * Get baan
     *
     * @return string 
     */
    public function getBaan()
    {
        return $this->baan;
    }
}
