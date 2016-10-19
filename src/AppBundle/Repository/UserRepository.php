<?php

namespace AppBundle\Repository;

/**
 * UniversityRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */


class UserRepository extends \Doctrine\ORM\EntityRepository
{

    function findAllUsers()
    {
        return $this->getEntityManager()
            ->createQueryBuilder('u')
            ->select('u.id, u.email, u.username, u.fullName')

            ->from('AppBundle:User', 'u')
            ->getQuery()
            ->getResult();
    }

    function checkIfUserExist($service, $userId)
    {
        $searched_user = $this->findBy(array(
            $service . "Id" => $userId,
        ));

        if (count($searched_user) == 1) {
            return $searched_user;
        } else {
            return false;
        }
    }

    function checkIfNewUsernameExist($username)
    {
        $searched_user = $this->findBy(array(
            'username' => $username
        ));
        if (count($searched_user) == 1) {
            return true;
        } else {
            return false;
        }
    }

    function checkIfUsernameExist($given_user, $own_username)
    {

        $searched_user = $this->findBy(array(
            'username' => $given_user->getUsername(),
        ));

        if (count($searched_user) == 1 && $own_username != $searched_user[0]->getUsername()) {
            return true;
        } else {
            return false;
        }

    }

    function checkIfUsernameExistByUsername($given_user_name, $own_username)
    {

        $searched_user = $this->findBy(array(
            'username' => $given_user_name,
        ));
        if (count($searched_user) == 1 && $own_username != $searched_user[0]->getUsername()) {
            return true;
        } else {
            return false;
        }

    }

    function checkIfNewEmailExist($email)
    {
        $searched_user = $this->findBy(array(
            'email' => $email,
        ));
        if (count($searched_user) == 1) {
            return true;
        } else {
            return false;
        }
    }

    function checkIfEmailExist($given_user, $own_mail)
    {
        $searched_user = $this->findBy(array(
            'email' => $given_user->getEmail(),
        ));

        if (count($searched_user) == 1 && $own_mail != $searched_user[0]->getEmail()) {
            return true;
        } else {
            return false;
        }
    }

    function checkIfEmailExistByEmail($given_email, $own_email)
    {
        $searched_user = $this->findBy(array(
            'email' => $given_email,
        ));
        if (count($searched_user) == 1 && $own_email != $searched_user[0]->getEmail()) {
            return true;
        } else {
            return false;
        }
    }



    function getNonApprovedUserSearchResult($searchQuery,$emailQuery ,$fullNameQuery,$enabledQuery, $pageNumber, $pageSize,$sort){
        $firstResult = ($pageNumber - 1) * $pageSize;
        $qb= $this->getEntityManager()
            ->createQueryBuilder('u')
            ->select('u.id as userId, u.username,u.email,u.fullName,un.universityName,c.campusName,u.enabled,u.roles,u.profilePicture,u.registrationDateTime')
            ->from('AppBundle:User', 'u')
            ->innerJoin('AppBundle:Campus', 'c', 'WITH', 'u.campus = c.id')
            ->innerJoin('AppBundle:University', 'un', 'WITH', 'un.id = c.university')
            ->andwhere('u.username LIKE :query ')
            ->andwhere('u.email LIKE :emailQuery ')
            ->andwhere('u.fullName LIKE :fullNameQuery ')

            ->andwhere('u.roles NOT LIKE :role ')
            ->andwhere("u.adminApproved= 'No'")
            ->setParameter('query', '%' . $searchQuery . '%')
            ->setParameter('emailQuery', '%' . $emailQuery . '%')
            ->setParameter('fullNameQuery', '%' . $fullNameQuery. '%')

            ->setParameter('role','%ROLE_ADMIN_USER%')
            ->setMaxResults($pageSize)
            ->setFirstResult($firstResult);

        if($enabledQuery===true){
            $qb->andwhere('u.enabled =:enabledQuery')
                ->setParameter('enabledQuery',  $enabledQuery);
        }elseif($enabledQuery===false){
            $qb->andwhere('u.enabled =:enabledQuery')
                ->setParameter('enabledQuery',  $enabledQuery);
        }

        foreach($sort as  $key => $value){
            $qb->addOrderBy("u.".$key,$value);
        }
        return $qb->getQuery()
            ->getResult();

    }


    public function getNonApprovedUserSearchNumber($searchQuery,$emailQuery,$fullNameQuery,$enabledQuery)
    {
        $qb =  $this->getEntityManager()->createQueryBuilder('u')
            ->select('COUNT(u)')
            ->from('AppBundle:User', 'u')
            ->innerJoin('AppBundle:Campus', 'c', 'WITH', 'u.campus = c.id')
            ->innerJoin('AppBundle:University', 'un', 'WITH', 'un.id = c.university')
            ->andwhere('u.username LIKE :query ')
            ->andwhere('u.email LIKE :emailQuery ')
            ->andwhere('u.fullName LIKE :fullNameQuery ')

            ->andwhere('u.roles NOT LIKE :role ')
            ->setParameter('query', '%' . $searchQuery . '%')
            ->setParameter('emailQuery', '%' . $emailQuery . '%')
            ->setParameter('fullNameQuery', '%' . $fullNameQuery. '%')

            ->setParameter('role','%ROLE_ADMIN_USER%')
            ->andwhere("u.adminApproved= 'No'");
        if($enabledQuery===true){
            $qb->andwhere('u.enabled =:enabledQuery')
                ->setParameter('enabledQuery',  $enabledQuery);
        }elseif($enabledQuery===false){
            $qb->andwhere('u.enabled =:enabledQuery')
                ->setParameter('enabledQuery',  $enabledQuery);
        }
        return $qb->getQuery()
            ->getSingleScalarResult();
    }



    function getAdminUserSearchResult($searchQuery,$emailQuery, $pageNumber, $pageSize,$sort){
        $firstResult = ($pageNumber - 1) * $pageSize;
        $qb= $this->getEntityManager()
            ->createQueryBuilder('u')
            ->select('u.id as userId, u.username,u.email,u.fullName,u.enabled,u.roles,u.profilePicture')
            ->from('AppBundle:User', 'u')
            ->andwhere('u.email LIKE :emailQuery ')
            ->andwhere('u.username LIKE :query ')
            ->andwhere('u.roles LIKE :role ')
            ->setParameter('query', '%' . $searchQuery . '%')
            ->setParameter('emailQuery', '%' . $emailQuery . '%')
            ->setParameter('role','%ROLE_ADMIN_USER%')
            ->setMaxResults($pageSize)
            ->setFirstResult($firstResult);


        foreach($sort as  $key => $value){
            $qb->addOrderBy("u.".$key,$value);
        }
        return($qb->getQuery()
            ->getResult()) ;

    }


    public function getAdminUserSearchNumber($searchQuery,$emailQuery)
    {
        return $this->getEntityManager()->createQueryBuilder('u')
            ->select('COUNT(u)')
            ->from('AppBundle:User', 'u')
            ->andwhere('u.username LIKE :query ')
            ->andwhere('u.email LIKE :emailQuery ')
            ->andwhere('u.roles LIKE :role ')
            ->setParameter('query', '%' . $searchQuery . '%')
            ->setParameter('emailQuery', '%' . $emailQuery . '%')
            ->setParameter('role','%ROLE_ADMIN_USER%')
            ->getQuery()
            ->getSingleScalarResult();
    }


    function getApprovedUserSearchResult($searchQuery,$emailQuery,$fullNameQuery,$universityNameQuery,$campusNameQuery,$enabledQuery, $pageNumber, $pageSize,$sort){
        $firstResult = ($pageNumber - 1) * $pageSize;
        $qb= $this->getEntityManager()
            ->createQueryBuilder('u')
            ->select('u.id as userId, u.username,u.email,u.fullName,un.universityName,c.campusName,u.enabled,u.profilePicture,u.registrationDateTime')
            ->from('AppBundle:User', 'u')
            ->innerJoin('AppBundle:Campus', 'c', 'WITH', 'u.campus = c.id')
            ->innerJoin('AppBundle:University', 'un', 'WITH', 'un.id = c.university')
            ->andwhere('u.username LIKE :query ')
            ->andwhere('u.email LIKE :emailQuery ')
            ->andwhere('u.fullName LIKE :fullNameQuery ')
            ->andwhere('un.universityName LIKE :universityNameQuery ')
            ->andwhere('c.campusName LIKE :campusNameQuery ')
            ->andwhere('u.roles NOT LIKE :role ')
            ->andwhere("u.adminApproved= 'Yes'")
            ->setParameter('query', '%' . $searchQuery . '%')
            ->setParameter('emailQuery', '%' . $emailQuery . '%')
            ->setParameter('fullNameQuery', '%' . $fullNameQuery. '%')
            ->setParameter('universityNameQuery', '%' . $universityNameQuery. '%')
            ->setParameter('campusNameQuery', '%' . $campusNameQuery. '%')
            ->setParameter('role','%ROLE_ADMIN_USER%')
            ->setMaxResults($pageSize)
            ->setFirstResult($firstResult);

        if($enabledQuery===true){
            $qb->andwhere('u.enabled =:enabledQuery')
                ->setParameter('enabledQuery',  $enabledQuery);
        }elseif($enabledQuery===false){
            $qb->andwhere('u.enabled =:enabledQuery')
                ->setParameter('enabledQuery',  $enabledQuery);
        }

        foreach($sort as  $key => $value){
            $qb->addOrderBy("u.".$key,$value);
        }
        return $qb->getQuery()
            ->getResult();

    }


    public function getApprovedUserSearchNumber($searchQuery,$emailQuery,$fullNameQuery,$universityNameQuery,$campusNameQuery,$enabledQuery)
    {
        $qb= $this->getEntityManager()->createQueryBuilder('u')
            ->select('COUNT(u)')
            ->from('AppBundle:User', 'u')
            ->innerJoin('AppBundle:Campus', 'c', 'WITH', 'u.campus = c.id')
            ->innerJoin('AppBundle:University', 'un', 'WITH', 'un.id = c.university')
            ->andwhere('u.username LIKE :query ')
            ->andwhere('u.email LIKE :emailQuery ')
            ->andwhere('u.fullName LIKE :fullNameQuery ')
            ->andwhere('un.universityName LIKE :universityNameQuery ')
            ->andwhere('c.campusName LIKE :campusNameQuery ')
            ->andwhere('u.roles NOT LIKE :role ')
            ->setParameter('query', '%' . $searchQuery . '%')
            ->setParameter('emailQuery', '%' . $emailQuery . '%')
            ->setParameter('fullNameQuery', '%' . $fullNameQuery. '%')
            ->setParameter('universityNameQuery', '%' . $universityNameQuery. '%')
            ->setParameter('campusNameQuery', '%' . $campusNameQuery. '%')
            ->setParameter('role','%ROLE_ADMIN_USER%')
            ->andwhere("u.adminApproved= 'Yes'");

        if($enabledQuery===true){
            $qb->andwhere('u.enabled =:enabledQuery')
                ->setParameter('enabledQuery',  $enabledQuery);
        }elseif($enabledQuery===false){
            $qb->andwhere('u.enabled =:enabledQuery')
                ->setParameter('enabledQuery',  $enabledQuery);
        }

            return $qb->getQuery()
            ->getSingleScalarResult();
    }

    public function approveUsers($users){
        $conditions = array();
        foreach ($users as $user) {
            array_push($conditions, "u.id = '" . $user['userId'] . "'");
        }

        $queryBuilderUser = $this->getEntityManager()->createQueryBuilder('u');


        $queryBuilderUser
            ->update('AppBundle:User', 'u')
            ->set('u.adminApproved', "'Yes'");

        $orX = $queryBuilderUser->expr()->orX();
        $orX->addMultiple($conditions);
        $queryBuilderUser->add('where', $orX);


        return $queryBuilderUser->getQuery()->execute();

    }
}
