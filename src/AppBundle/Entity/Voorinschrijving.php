<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="voorinschrijving")
 */
class Voorinschrijving
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(length=256)
     */
    private $token;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $usedAt;

    /**
     * @ORM\Column(length=256)
     */
    private $tokenSentTo;

    public function getAll()
    {
        $token = [
            'id' => $this->id,
            'token' => $this->token,
            'createdAt' => $this->createdAt,
            'usedAt' => $this->usedAt,
            'tokenSentTo' => $this->tokenSentTo,
        ];
        return $token;
    }

    /**
     * @return mixed
     */
    public function getTokenSentTo()
    {
        return $this->tokenSentTo;
    }

    /**
     * @param mixed $tokenSentTo
     */
    public function setTokenSentTo($tokenSentTo)
    {
        $this->tokenSentTo = $tokenSentTo;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param mixed $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param mixed $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return mixed
     */
    public function getUsedAt()
    {
        return $this->usedAt;
    }

    /**
     * @param mixed $usedAt
     */
    public function setUsedAt($usedAt)
    {
        $this->usedAt = $usedAt;
    }
}
