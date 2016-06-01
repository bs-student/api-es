<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * BookDealInfoRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class BookDealRepository extends EntityRepository
{
    public function getStudentBooksWithMultipleISBN($books, $campusId)
    {


        $conditions = array();
        foreach ($books as $book) {
            array_push($conditions, "b.bookIsbn10 = '" . $book['bookIsbn'] . "'");
        }


        $queryBuilderBook = $this->getEntityManager()->createQueryBuilder('b');


        $queryBuilderBook->select('b.bookIsbn10,MIN(bd.bookPriceSell) AS bookPriceSell')
            ->from('AppBundle:Book', 'b')
            ->innerJoin('AppBundle:BookDeal', 'bd', 'WITH', 'b.id = bd.book')
            ->groupBy('b.bookIsbn10');

        //If Logged In then only Send campus lowest
        if ($campusId != null) {
            $queryBuilderBook->innerJoin('AppBundle:User', 'u', 'WITH', 'u.id = bd.seller');
        }

        $orX = $queryBuilderBook->expr()->orX();
        $orX->addMultiple($conditions);
        $queryBuilderBook->add('where', $orX);

        $queryBuilderBook->add('where', $orX);

        //If Logged In then only Send campus lowest
        if ($campusId != null) {
            $queryBuilderBook->andWhere('u.campus = ' . $campusId);
        }


        return ($queryBuilderBook->getQuery()->getResult());

//        die($queryBuilderBook->getQuery()->getSql());


//        return $this->getEntityManager()
//            ->createQueryBuilder('u')
//            ->select('c.id as campusId, u.universityName, c.campusName, s.stateShortName, co.countryName')
//
//            ->from('AppBundle:University', 'u')
//
//            ->innerJoin('AppBundle:Campus', 'c','WITH', 'u.id = c.university')
//
//            ->andwhere('u.universityStatus=\'Activated\'')
//            ->andwhere('c.campusName LIKE :query OR u.universityName LIKE :query OR co.countryName LIKE :query OR s.stateName LIKE :query')
//
//
//
//            ->setParameter('query', '%'.$searchQuery.'%')
//            ->getQuery()
//            ->getResult();

    }

    function getCampusDealsByIsbn($isbn, $campusId = null)
    {
        if($campusId == null){
            return null;
        }

        $qb = $this->getEntityManager()
            ->createQueryBuilder('b')
            ->select('b.id as bookId,bd.id as bookDealId,b.bookIsbn10,u.username, bd.bookPriceSell, bd.bookCondition,bd.bookIsHighlighted, bd.bookHasNotes,
                    bd.bookComment,bd.bookContactMethod,bd.bookPaymentMethodCaShOnExchange,bd.bookPaymentMethodCheque,
                    bd.bookIsAvailablePublic,bd.bookAvailableDate,c.campusName,un.universityName,s.stateName,s.stateShortName,
                    co.countryName')

            ->from('AppBundle:Book', 'b')
            ->innerJoin('AppBundle:BookDeal', 'bd', 'WITH', 'b.id = bd.book')
            ->innerJoin('AppBundle:User', 'u', 'WITH', 'u.id = bd.seller')
            ->innerJoin('AppBundle:Campus', 'c', 'WITH', 'c.id = u.campus')
            ->innerJoin('AppBundle:University', 'un', 'WITH', 'un.id = c.university')
            ->innerJoin('AppBundle:State', 's', 'WITH', 's.id = c.state')
            ->innerJoin('AppBundle:Country', 'co', 'WITH', 'co.id = s.country')
            ->andwhere('b.bookIsbn10 = :isbn')

            ->andwhere('bd.bookStatus = ' . "'Activated'")
            ->andwhere('bd.bookSellingStatus = ' . "'Selling'")
            ->setParameter('isbn', $isbn);
        if ($campusId != null) {
            $qb->andwhere('u.campus = :campusId')
                ->setParameter('campusId', $campusId);
        }

        return $qb->getQuery()
            ->getResult();


    }

    function getBooksIHaveContactedFor($userId)
    {
        return $this->getEntityManager()
            ->createQueryBuilder('b')
            ->select("b.id as bookId,
                      b.bookIsbn10 as bookIsbn,
                      b.bookTitle,
                      b.bookDirectorAuthorArtist,
                      b.bookEdition,
                      b.bookPublisher,
                      b.bookPublishDate,
                      b.bookBinding,
                      b.bookImage,
                      u.username as sellerUsername,
                      bd.bookContactHomeNumber as sellerHomeNumber,
                      bd.bookContactCellNumber as sellerCellNumber,
                      bd.bookContactEmail as sellerEmail,
                      bd.id as bookDealId,
                      bd.bookPriceSell,
                      bd.bookCondition,
                      bd.bookIsHighlighted,
                      bd.bookHasNotes,
                      bd.bookComment,
                      bd.bookContactMethod,
                      bd.bookPaymentMethodCaShOnExchange,
                      bd.bookPaymentMethodCheque,
                      bd.bookIsAvailablePublic,
                      bd.bookAvailableDate,
                      c.campusName,
                      un.universityName,
                      s.stateName,
                      s.stateShortName,
                      co.countryName,
                      con.id as contactId,
                      con.contactDateTime,
                      con.buyerHomePhone,
                      con.buyerCellPhone")

            ->from('AppBundle:Book', 'b')
            ->innerJoin('AppBundle:BookDeal', 'bd', 'WITH', 'b.id = bd.book')
            ->innerJoin('AppBundle:User', 'u', 'WITH', 'u.id = bd.seller')
            ->innerJoin('AppBundle:Campus', 'c', 'WITH', 'c.id = u.campus')
            ->innerJoin('AppBundle:University', 'un', 'WITH', 'un.id = c.university')
            ->innerJoin('AppBundle:State', 's', 'WITH', 's.id = c.state')
            ->innerJoin('AppBundle:Country', 'co', 'WITH', 'co.id = s.country')
            ->innerJoin('AppBundle:Contact', 'con', 'WITH', 'con.bookDeal = bd.id')


            ->andwhere('bd.bookStatus = ' . "'Activated'")
            ->andwhere('bd.bookSellingStatus = ' . "'Selling'")
            ->andwhere('con.buyer= :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();

    }

    function getBooksIHaveCreated($userId,$pageNumber,$pageSize)
    {
        $firstResult = ($pageNumber - 1) * $pageSize;
        return $this->getEntityManager()
            ->createQueryBuilder('b')
            ->select("b.id as bookId,
                      b.bookIsbn10 as bookIsbn,
                      b.bookTitle,
                      b.bookDirectorAuthorArtist,
                      b.bookEdition,
                      b.bookPublisher,
                      b.bookPublishDate,
                      b.bookBinding,
                      b.bookImage,
                      u.username as sellerUsername,
                      bd.bookContactHomeNumber as sellerHomeNumber,
                      bd.bookContactCellNumber as sellerCellNumber,
                      bd.bookContactEmail as sellerEmail,
                      bd.id as bookDealId,
                      bd.bookPriceSell,
                      bd.bookCondition,
                      bd.bookIsHighlighted,
                      bd.bookHasNotes,
                      bd.bookComment,
                      bd.bookContactMethod,
                      bd.bookPaymentMethodCaShOnExchange,
                      bd.bookPaymentMethodCheque,
                      bd.bookIsAvailablePublic,
                      bd.bookAvailableDate,
                      bd.bookStatus,
                      bd.bookViewCount,
                      c.campusName,
                      un.universityName,
                      s.stateName,
                      s.stateShortName,
                      co.countryName")

            ->from('AppBundle:Book', 'b')
            ->innerJoin('AppBundle:BookDeal', 'bd', 'WITH', 'b.id = bd.book')
            ->innerJoin('AppBundle:User', 'u', 'WITH', 'u.id = bd.seller')
            ->innerJoin('AppBundle:Campus', 'c', 'WITH', 'c.id = u.campus')
            ->innerJoin('AppBundle:University', 'un', 'WITH', 'un.id = c.university')
            ->innerJoin('AppBundle:State', 's', 'WITH', 's.id = c.state')
            ->innerJoin('AppBundle:Country', 'co', 'WITH', 'co.id = s.country')
//            ->innerJoin('AppBundle:Contact', 'con','WITH', 'con.bookDeal = bd.id')


//            ->andwhere('bd.bookStatus = '."'Activated'")
            ->andwhere('bd.bookSellingStatus = ' . "'Selling'")
            ->andwhere('bd.seller= :userId')
            ->setParameter('userId', $userId)
            ->orderBy('bd.bookSubmittedDateTime','DESC')
            ->setMaxResults($pageSize)
            ->setFirstResult($firstResult)
            ->getQuery()
            ->getResult();
    }

    function getBooksIHaveCreatedTotalNumber($userId)
    {
        return $this->getEntityManager()
            ->createQueryBuilder('b')
            ->select("count(bd)")

            ->from('AppBundle:Book', 'b')
            ->innerJoin('AppBundle:BookDeal', 'bd', 'WITH', 'b.id = bd.book')
            ->innerJoin('AppBundle:User', 'u', 'WITH', 'u.id = bd.seller')
            ->innerJoin('AppBundle:Campus', 'c', 'WITH', 'c.id = u.campus')
            ->innerJoin('AppBundle:University', 'un', 'WITH', 'un.id = c.university')
            ->innerJoin('AppBundle:State', 's', 'WITH', 's.id = c.state')
            ->innerJoin('AppBundle:Country', 'co', 'WITH', 'co.id = s.country')

            ->andwhere('bd.bookSellingStatus = ' . "'Selling'")
            ->andwhere('bd.seller= :userId')
            ->setParameter('userId', $userId)

            ->getQuery()
            ->getSingleScalarResult();

    }



    function getBooksIHaveCreatedAndSold($userId,$pageNumber,$pageSize)
    {
        $firstResult = ($pageNumber - 1) * $pageSize;
        return $this->getEntityManager()
            ->createQueryBuilder('b')
            ->select("b.id as bookId,
                      b.bookIsbn10 as bookIsbn,
                      b.bookTitle,
                      b.bookDirectorAuthorArtist,
                      b.bookEdition,
                      b.bookPublisher,
                      b.bookPublishDate,
                      b.bookBinding,
                      b.bookImage,
                      u.username as sellerUsername,
                      bd.bookContactHomeNumber as sellerHomeNumber,
                      bd.bookContactCellNumber as sellerCellNumber,
                      bd.bookContactEmail as sellerEmail,
                      bd.id as bookDealId,
                      bd.bookPriceSell,
                      bd.bookCondition,
                      bd.bookIsHighlighted,
                      bd.bookHasNotes,
                      bd.bookComment,
                      bd.bookContactMethod,
                      bd.bookPaymentMethodCaShOnExchange,
                      bd.bookPaymentMethodCheque,
                      bd.bookIsAvailablePublic,
                      bd.bookAvailableDate,
                      bd.bookStatus,
                      bd.bookViewCount,
                      IDENTITY(bd.buyer) AS buyerId,
                      c.campusName,
                      un.universityName,
                      s.stateName,
                      s.stateShortName,
                      co.countryName")

            ->from('AppBundle:Book', 'b')
            ->innerJoin('AppBundle:BookDeal', 'bd', 'WITH', 'b.id = bd.book')
            ->innerJoin('AppBundle:User', 'u', 'WITH', 'u.id = bd.seller')
            ->innerJoin('AppBundle:Campus', 'c', 'WITH', 'c.id = u.campus')
            ->innerJoin('AppBundle:University', 'un', 'WITH', 'un.id = c.university')
            ->innerJoin('AppBundle:State', 's', 'WITH', 's.id = c.state')
            ->innerJoin('AppBundle:Country', 'co', 'WITH', 'co.id = s.country')
//            ->innerJoin('AppBundle:Contact', 'con','WITH', 'con.bookDeal = bd.id')


            ->andwhere('bd.bookStatus = '."'Activated'")
            ->andwhere('bd.bookSellingStatus = ' . "'Sold'")
            ->andwhere('bd.seller= :userId')
            ->setParameter('userId', $userId)
            ->orderBy('bd.bookSubmittedDateTime','DESC')
            ->setMaxResults($pageSize)
            ->setFirstResult($firstResult)
            ->getQuery()
            ->getResult();
    }

    function getBooksIHaveCreatedAndSoldTotalNumber($userId)
    {
        return $this->getEntityManager()
            ->createQueryBuilder('b')
            ->select("count(bd)")

            ->from('AppBundle:Book', 'b')
            ->innerJoin('AppBundle:BookDeal', 'bd', 'WITH', 'b.id = bd.book')
            ->innerJoin('AppBundle:User', 'u', 'WITH', 'u.id = bd.seller')
            ->innerJoin('AppBundle:Campus', 'c', 'WITH', 'c.id = u.campus')
            ->innerJoin('AppBundle:University', 'un', 'WITH', 'un.id = c.university')
            ->innerJoin('AppBundle:State', 's', 'WITH', 's.id = c.state')
            ->innerJoin('AppBundle:Country', 'co', 'WITH', 'co.id = s.country')

            ->andwhere('bd.bookSellingStatus = ' . "'Sold'")
            ->andwhere('bd.seller= :userId')
            ->setParameter('userId', $userId)

            ->getQuery()
            ->getSingleScalarResult();

    }

    function getBooksIHaveBought($userId){

        return $this->getEntityManager()
            ->createQueryBuilder('b')
            ->select("b.id as bookId,
                      b.bookIsbn10 as bookIsbn,
                      b.bookTitle,
                      b.bookDirectorAuthorArtist,
                      b.bookEdition,
                      b.bookPublisher,
                      b.bookPublishDate,
                      b.bookBinding,
                      b.bookImage,
                      u.username as sellerUsername,
                      bd.bookContactHomeNumber as sellerHomeNumber,
                      bd.bookContactCellNumber as sellerCellNumber,
                      bd.bookContactEmail as sellerEmail,
                      bd.id as bookDealId,
                      bd.bookPriceSell,
                      bd.bookCondition,
                      bd.bookIsHighlighted,
                      bd.bookHasNotes,
                      bd.bookComment,
                      bd.bookContactMethod,
                      bd.bookPaymentMethodCaShOnExchange,
                      bd.bookPaymentMethodCheque,
                      bd.bookIsAvailablePublic,
                      bd.bookAvailableDate,
                      bd.bookStatus,
                      bd.bookViewCount,
                      IDENTITY(bd.buyer) AS buyerId,
                      c.campusName,
                      un.universityName,
                      s.stateName,
                      s.stateShortName,
                      co.countryName,
                      con.id as contactId,
                      con.contactDateTime,
                      con.buyerHomePhone,
                      con.buyerCellPhone
                      ")

            ->from('AppBundle:Book', 'b')
            ->innerJoin('AppBundle:BookDeal', 'bd', 'WITH', 'b.id = bd.book')
            ->innerJoin('AppBundle:User', 'u', 'WITH', 'u.id = bd.seller')
            ->innerJoin('AppBundle:Campus', 'c', 'WITH', 'c.id = u.campus')
            ->innerJoin('AppBundle:University', 'un', 'WITH', 'un.id = c.university')
            ->innerJoin('AppBundle:State', 's', 'WITH', 's.id = c.state')
            ->innerJoin('AppBundle:Country', 'co', 'WITH', 'co.id = s.country')
            ->innerJoin('AppBundle:Contact', 'con','WITH', 'con.bookDeal = bd.id')


            ->andwhere('bd.bookStatus = '."'Activated'")
            ->andwhere('bd.bookSellingStatus = ' . "'Sold'")
            ->andwhere('bd.buyer = :userId')
            ->andwhere('con.buyer = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();
    }

    function getContactsOfBookDeals($bookDeals)
    {
        if ($bookDeals != null) {

            $conditions = array();
            foreach ($bookDeals as $bookDeal) {
                array_push($conditions, "c.bookDeal = '" . $bookDeal['bookDealId'] . "'");
            }


            $queryBuilderBook = $this->getEntityManager()->createQueryBuilder('b');


            $queryBuilderBook
                ->select('
                    c.id as contactId,
                    c.contactDateTime,
                    c.buyerHomePhone,
                    c.buyerCellPhone,
                    c.buyerNickName,
                    c.buyerEmail,
                    IDENTITY(c.bookDeal) AS bookDealId,
                    IDENTITY(c.buyer) AS buyerId
                    ')
                ->from('AppBundle:Contact', 'c')
                ->innerJoin('AppBundle:BookDeal', 'bd', 'WITH', 'bd.id = c.bookDeal')

            ;
//            ->groupBy('c.bookIsbn10');





            $orX = $queryBuilderBook->expr()->orX();
            $orX->addMultiple($conditions);
            $queryBuilderBook->add('where', $orX);

//            $queryBuilderBook->add('where', $orX);
//            $queryBuilderBook->innerJoin('AppBundle:User', 'u', 'WITH', 'c.buyer = u.id');
//        if($campusId!=null){
//            $queryBuilderBook->andWhere('u.campus = '.$campusId);
//        }

//die($queryBuilderBook->getQuery()->getSQL());

            return ($queryBuilderBook->getQuery()->getResult());
        }
    }

    function getPublicUserWhoBoughtBookDeal($bookDealId){
        return $this->getEntityManager()
            ->createQueryBuilder('b')
            ->select("c.buyerNickName ")

            ->from('AppBundle:Contact', 'c')

            ->andwhere('c.soldToThatBuyer = ' . "'Yes'")
            ->andwhere('c.bookDeal= :bookDealId')
            ->setParameter('bookDealId', $bookDealId)
            ->getQuery()
            ->getResult();
    }

    function increaseBookViewCounter($onCampusDeals){


        $conditions = array();
        foreach ($onCampusDeals as $bookDeal) {
            array_push($conditions, "bd.id = '" . $bookDeal['bookDealId'] . "'");
        }

        $queryBuilderBook =  $this
            ->createQueryBuilder('bd')
            ->update('AppBundle:BookDeal', 'bd')
            ->set('bd.bookViewCount', 'bd.bookViewCount+1');

        $orX = $queryBuilderBook->expr()->orX();
        $orX->addMultiple($conditions);
        $queryBuilderBook->add('where', $orX);


           $queryBuilderBook ->getQuery()
            ->execute();
    }

    function getAllBookDealSearchResult($searchQuery, $pageNumber, $pageSize,$sort){

        $firstResult = ($pageNumber - 1) * $pageSize;
        $qb= $this->getEntityManager()
            ->createQueryBuilder('bd')
            ->select('b.id as bookId,
                      b.bookIsbn10 as bookIsbn,
                      b.bookTitle,
                      b.bookDirectorAuthorArtist,
                      b.bookEdition,
                      b.bookPublisher,
                      b.bookPublishDate,
                      b.bookBinding,
                      b.bookImage,
                      u.username as sellerUsername,
                      bd.bookContactHomeNumber as sellerHomeNumber,
                      bd.bookContactCellNumber as sellerCellNumber,
                      bd.bookContactEmail as sellerEmail,
                      bd.id as bookDealId,
                      bd.bookPriceSell,
                      bd.bookCondition,
                      bd.bookIsHighlighted,
                      bd.bookHasNotes,
                      bd.bookComment,
                      bd.bookContactMethod,
                      bd.bookPaymentMethodCaShOnExchange,
                      bd.bookPaymentMethodCheque,
                      bd.bookIsAvailablePublic,
                      bd.bookAvailableDate,
                      bd.bookStatus,
                      bd.bookViewCount,
                      c.campusName,
                      un.universityName,
                      s.stateName,
                      s.stateShortName,
                      co.countryName
            ')
            ->from('AppBundle:BookDeal', 'bd')
            ->innerJoin('AppBundle:Book', 'b', 'WITH', 'bd.book = b.id')
            ->innerJoin('AppBundle:User', 'u', 'WITH', 'bd.seller = u.id')
            ->innerJoin('AppBundle:Campus', 'c', 'WITH', 'u.campus = c.id')
            ->innerJoin('AppBundle:University', 'un', 'WITH', 'un.id = c.university')
            ->innerJoin('AppBundle:State', 's', 'WITH', 's.id = c.state')
            ->innerJoin('AppBundle:Country', 'co', 'WITH', 's.country = co.id')
            ->andwhere('b.bookTitle LIKE :query ')
            ->setParameter('query', '%' . $searchQuery . '%')
            ->setMaxResults($pageSize)
            ->setFirstResult($firstResult);


        foreach($sort as  $key => $value){
            $qb->addOrderBy("bd.".$key,$value);
        }
        return $qb->getQuery()
            ->getResult();

    }

    public function getAllBookDealSearchNumber($searchQuery)
    {
        return $this->getEntityManager()
            ->createQueryBuilder('u')
            ->select('COUNT(bd)')
            ->from('AppBundle:BookDeal', 'bd')
            ->innerJoin('AppBundle:Book', 'b', 'WITH', 'bd.book = b.id')
            ->innerJoin('AppBundle:User', 'u', 'WITH', 'bd.seller = u.id')
            ->innerJoin('AppBundle:Campus', 'c', 'WITH', 'u.campus = c.id')
            ->innerJoin('AppBundle:University', 'un', 'WITH', 'un.id = c.university')
            ->innerJoin('AppBundle:State', 's', 'WITH', 's.id = c.state')
            ->innerJoin('AppBundle:Country', 'co', 'WITH', 's.country = co.id')
            ->andwhere('b.bookTitle LIKE :query ')
            ->setParameter('query', '%' . $searchQuery . '%')
            ->getQuery()
            ->getSingleScalarResult();
    }

    function getLowestDealPriceInCampus($userCampusId,$bookIsbn){
        return $this->getEntityManager()
            ->createQueryBuilder('bd')
            ->select('MIN(bd.bookPriceSell)')

            ->from('AppBundle:BookDeal', 'bd')
            ->innerJoin('AppBundle:User', 'u', 'WITH', 'u.id = bd.seller')
            ->innerJoin('AppBundle:Campus', 'c', 'WITH', 'c.id = u.campus')
            ->innerJoin('AppBundle:Book', 'b', 'WITH', 'b.id = bd.book')
            ->andwhere('b.bookIsbn10 = :isbn')
            ->andwhere('u.campus = :campus')
            ->andwhere('bd.bookStatus = ' . "'Activated'")
            ->andwhere('bd.bookSellingStatus = ' . "'Selling'")
            ->setParameter('isbn', $bookIsbn)
            ->setParameter('campus', $userCampusId)
            ->getQuery()
            ->getResult();
    }
}
