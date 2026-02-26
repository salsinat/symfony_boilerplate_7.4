<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @return Product[]
     */
    public function findAllSortedByPrice(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.price', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get products count by type
     */
    public function getCountByType(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT type, COUNT(*) as count
            FROM product
            GROUP BY type
        ';

        return $conn->executeQuery($sql)->fetchAllAssociative();
    }

    /**
     * Get low stock products (stock < 5)
     */
    public function getLowStockProducts(int $threshold = 5): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.stock < :threshold')
            ->setParameter('threshold', $threshold)
            ->orderBy('p.stock', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get total stock value
     */
    public function getTotalStockValue(): int
    {
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.price * p.stock) as value')
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }
}
