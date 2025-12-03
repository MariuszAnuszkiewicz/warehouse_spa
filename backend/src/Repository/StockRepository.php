<?php

namespace App\Repository;

use App\Entity\Stock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Stock>
 */
class StockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stock::class);
    }

    //    /**
    //     * @return Stock[] Returns an array of Stock objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Stock
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function updateQuantityInStockByProduct(int $quantity, string $product)
    {
        return $this->createQueryBuilder('s')
            ->update()
            ->set('s.quantityInStock', ':quantity')
            ->where('s.productName = :product')
            ->setParameter('quantity', $quantity)
            ->setParameter('product', $product)
            ->getQuery()
            ->execute();
    }

    public function updateQuantityInStockById(int $quantity, int $id)
    {
        return $this->createQueryBuilder('s')
            ->update()
            ->set('s.quantityInStock', ':quantity')
            ->where('s.id = :id')
            ->setParameter('quantity', $quantity)
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
    }

    public function findByProductNames(array $names): array
    {
        return $this->createQueryBuilder('s')
            ->select('s')
            ->join('s.product', 'p')
            ->where('s.productName IN (:names)')
            ->setParameter('names', $names)
            ->getQuery()
            ->getResult();
    }
}
