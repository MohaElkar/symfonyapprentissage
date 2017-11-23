<?php

namespace OC\PlatformBundle\Repository;

/**
 * AdvertRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class AdvertRepository extends \Doctrine\ORM\EntityRepository
{
    // copy du findBy($id)
    public function myFindBy($id){
        $queryBuilder = $this->createQueryBuilder("a");
        $queryBuilder->where("a.id = :id")
                     ->setParameter("id", $id);

        return $queryBuilder->getQuery()->getResult();
    }

    public function findAuthorAndDate($author, $year){
        $queryBuilder = $this->createQueryBuilder("a");

        $queryBuilder->where("a.author = :author")
                     ->setParameter("author", $author)
                     ->andWhere("a.date = :year")
                     ->setParameter("year", $year)
                     ->orderBy("a.date", "DESC");

        return $queryBuilder->getQuery()->getResult();
    }
}
