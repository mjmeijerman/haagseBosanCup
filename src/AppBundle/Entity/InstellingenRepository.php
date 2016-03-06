<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class Instellingen
 * @package AppBundle\Entity
 */
class InstellingenRepository extends EntityRepository
{
    public function getTijdVol($datumGeopend)
    {
        $result = $this->createQueryBuilder('u')
            ->where('u.instelling = :tijdVol')
            ->andWhere('u.gewijzigd > :datumGeopend')
            ->setParameters([
                'datumGeopend' => $datumGeopend,
                'tijdVol' => 'tijdVol',
            ])
            ->getQuery()
            ->getOneOrNullResult();
        return $result;
    }
}
