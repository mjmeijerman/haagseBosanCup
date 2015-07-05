<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="content")
 */
class Content
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $gewijzigd;

    /**
     * @ORM\Column(length=156)
     */
    protected $pagina;

    /**
     * @ORM\Column(type="text")
     */
    protected $content;

    /**
     * Set gewijzigd
     *
     * @param \DateTime $gewijzigd
     * @return Content
     */
    public function setGewijzigd($gewijzigd)
    {
        $this->gewijzigd = $gewijzigd;

        return $this;
    }

    /**
     * Get gewijzigd
     *
     * @return \DateTime
     */
    public function getGewijzigd()
    {
        return $this->gewijzigd;
    }

    /**
     * Set pagina
     *
     * @param string $pagina
     * @return Content
     */
    public function setPagina($pagina)
    {
        $this->pagina = $pagina;

        return $this;
    }

    /**
     * Get pagina
     *
     * @return string
     */
    public function getPagina()
    {
        return $this->pagina;
    }

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
     * Set content
     *
     * @param string $content
     * @return Content
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string 
     */
    public function getContent()
    {
        return $this->content;
    }
}
