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

    public function getWedstrijdDagen()
    {
        $results = $this->createQueryBuilder('cc')
            ->select('cc.wedstrijddag')
            ->where('cc.wedstrijddag IS NOT NULL')
            ->orderBy('cc.wedstrijddag', 'ASC')
            ->distinct()
            ->getQuery()
            ->getResult();
        return $results;
    }

    public function getWedstrijdrondesPerDag($dag)
    {
        $results = $this->createQueryBuilder('cc')
            ->select('cc.wedstrijdronde')
            ->where('cc.wedstrijdronde IS NOT NULL')
            ->andWhere('cc.wedstrijddag = :dag')
            ->setParameter('dag', $dag)
            ->orderBy('cc.wedstrijdronde')
            ->distinct()
            ->getQuery()
            ->getResult();
        return $results;
    }

    public function getNiveausPerDagPerRondePerBaan($dag, $ronde, $baan)
    {
        $results = $this->createQueryBuilder('cc')
            ->join('cc.turnster', 'g')
            ->select('g.niveau, g.categorie')
            ->where('cc.wedstrijdronde IS NOT NULL')
            ->andWhere('cc.wedstrijddag = :dag')
            ->andWhere('cc.wedstrijdronde = :ronde')
            ->andWhere('cc.baan = :baan')
            ->setParameters([
                'dag' => $dag,
                'ronde' => $ronde,
                'baan' => $baan
            ])
            ->distinct()
            ->getQuery()
            ->getResult();
        return $results;
    }

    public function getBanenPerDag($dag)
    {
        $results = $this->createQueryBuilder('cc')
            ->select('cc.baan')
            ->where('cc.baan IS NOT NULL')
            ->andWhere('cc.wedstrijddag = :dag')
            ->setParameter('dag', $dag)
            ->orderBy('cc.baan')
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
            ->orderBy('cc.baan')
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


    public function getTePublicerenScores($toestel)
    {
        $results = $this->createQueryBuilder('cc')
            ->where('cc.gepubliceerd' . $toestel . ' = 0')
            ->andWhere('cc.updated' . $toestel . ' IS NOT NULL')
            ->getQuery()
            ->getResult();
        return $results;
    }
}
