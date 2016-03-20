<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class ScoresRepository
 * @package AppBundle\Entity
 */
class ScoresRepository extends EntityRepository
{
    public function getDagen()
    {
        $results = $this->createQueryBuilder('cc')
            ->select('cc.wedstrijddag')
            ->where('cc.wedstrijddag IS NOT NULL')
            ->distinct()
            ->getQuery()
            ->getResult();
        return $results;
    }

    public function getWedstrijdrondes()
    {
        $results = $this->createQueryBuilder('cc')
            ->select('cc.wedstrijdronde')
            ->where('cc.wedstrijdronde IS NOT NULL')
            ->distinct()
            ->getQuery()
            ->getResult();
        return $results;
    }

    public function getBanen()
    {
        $results = $this->createQueryBuilder('cc')
            ->select('cc.baan')
            ->where('cc.baan IS NOT NULL')
            ->distinct()
            ->getQuery()
            ->getResult();
        return $results;
    }

    public function getGroepen()
    {
        $results = $this->createQueryBuilder('cc')
            ->select('cc.groep')
            ->where('cc.groep IS NOT NULL')
            ->distinct()
            ->getQuery()
            ->getResult();
        return $results;
    }

    public function getLiveScoresPerBaanPerToestel($baan, $toestel)
    {
        $tijd = new \DateTime('now - 30 minutes');
        $results = $this->createQueryBuilder('cc')
            ->where('cc.baan = :baan')
            ->andWhere('cc.updated' . $toestel . ' > :tijd')
            ->setParameters([
                'tijd' => $tijd,
                'baan' => $baan,
            ])
            ->getQuery()
            ->getResult();
        return $results;
    }
}
