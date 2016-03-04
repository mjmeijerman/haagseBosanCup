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
            ->getQuery()
            ->getSingleScalarResult();
        return $bezettePlekken;
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
}
