<?php

namespace AppBundle\Repository;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * CountryRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CampusRepository extends \Doctrine\ORM\EntityRepository
{
    public function getCampus(){
        return $this->getEntityManager()
            ->createQueryBuilder('c')
            ->add('select', 'c')
            ->add('from', 'AppBundle:Campus c')
            ->innerJoin('AppBundle:University', 'u')
            ->where('c.university = u.id')
           /* ->getQuery()
            ->getResult()*/;

    }

    public function getCampusesByUniversityId($universityId){
        return $this->getEntityManager()
            ->createQueryBuilder('c')
            ->select('c.id,c.campusStatus, u.universityName, c.campusName, s.stateShortName,s.stateName, co.countryName')

            ->from('AppBundle:Campus', 'c')
            ->innerJoin('AppBundle:University', 'u','WITH', 'u.id = c.university')
            ->innerJoin('AppBundle:State', 's','WITH', 's.id = c.state')
            ->innerJoin('AppBundle:Country', 'co','WITH', 'co.id = s.country')
            ->andwhere('c.university = :universityId')
            ->setParameter('universityId', $universityId)
            ->getQuery()
            ->getResult();
    }

    public function getCampusDetailsWithUniversityAndState($campusId){
        return $this->getEntityManager()
            ->createQueryBuilder('c')
            ->select('c.id,c.campusName,c.campusStatus, u.universityName,u.universityUrl , s.stateShortName,s.stateName, co.countryName')

            ->from('AppBundle:Campus', 'c')
            ->innerJoin('AppBundle:University', 'u','WITH', 'u.id = c.university')
            ->innerJoin('AppBundle:State', 's','WITH', 's.id = c.state')
            ->innerJoin('AppBundle:Country', 'co','WITH', 'co.id = s.country')
            ->andwhere('c.id = :campusId')
            ->setParameter('campusId', $campusId)
            ->getQuery()
            ->getResult();
    }


//    public function getCampusSearchResults($searchQuery){
//
//
//
//        return $this->getEntityManager()
//            ->createQueryBuilder('c')
//            ->select('c.id, u.universityName, c.campusName, s.stateShortName, co.countryName')
//
//            ->from('AppBundle:Campus', 'c')
//            ->innerJoin('AppBundle:University', 'u','WITH', 'u.id = c.university')
//            ->innerJoin('AppBundle:State', 's','WITH', 's.id = c.state')
//            ->innerJoin('AppBundle:Country', 'co','WITH', 'co.id = s.country')
//            ->andwhere('c.campusName LIKE :query OR u.universityName LIKE :query OR co.countryName LIKE :query OR s.stateName LIKE :query')
//            ->andwhere('u.universityStatus="Activated"')
//            ->setParameter('query', '%'.$searchQuery.'%')
//            ->getQuery()
//            ->getResult();
//
//
//
//    }
}
