<?php

namespace AppBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * TurnsterRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TurnsterRepository extends EntityRepository
{
    public function getBezettePlekken()
    {
        $bezettePlekken = $this->createQueryBuilder('u')
            ->select('count(u.id)')
            ->where('u.afgemeld = 0')
            ->andWhere('u.wachtlijst = 0')
            ->getQuery()
            ->getSingleScalarResult();
        return $bezettePlekken;
    }

    public function getAantalWachtlijstPlekken()
    {
        $bezettePlekken = $this->createQueryBuilder('u')
            ->select('count(u.id)')
            ->where('u.afgemeld = 0')
            ->andWhere('u.wachtlijst = 1')
            ->getQuery()
            ->getSingleScalarResult();
        return $bezettePlekken;
    }

    public function getAantalAfgemeldeTurnsters($user)
    {
        $afgemeldeTurnsters = $this->createQueryBuilder('u')
            ->select('count(u.id)')
            ->where('u.afgemeld = 1')
            ->andWhere('u.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
        return $afgemeldeTurnsters;
    }

    public function getIngeschrevenTurnsters($user)
    {
        $ingeschrevenTurnsters = $this->createQueryBuilder('u')
            ->select('count(u.id)')
            ->where('u.afgemeld = 0')
            ->andWhere('u.wachtlijst = 0')
            ->andWhere('u.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
        return $ingeschrevenTurnsters;
    }

    public function getGereserveerdePlekken()
    {
        $gereserveerdePlekken = $this->createQueryBuilder('u')
            ->where('u.afgemeld = 0')
            ->andWhere('u.expirationDate IS NOT NULL')
            ->getQuery()
            ->getResult();
        return $gereserveerdePlekken;
    }

    public function getWachtlijstPlekken($limit)
    {
        $result = $this->createQueryBuilder('u')
            ->where('u.afgemeld = 0')
            ->andWhere('u.wachtlijst = 1')
            ->orderBy('u.id')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
        return $result;
    }

    public function getTijdVol()
    {
        $result = $this->createQueryBuilder('u')
            ->select('u.creationDate')
            ->where('u.wachtlijst = 0')
            ->orderBy('u.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();
        return $result;
    }
}
