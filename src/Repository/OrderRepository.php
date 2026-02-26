<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function findByUser($user): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.user = :user')
            ->setParameter('user', $user)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get orders count per day for the last 7 days
     */
    public function getOrdersPerDay(int $days = 7): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT DATE(created_at) as date, COUNT(*) as count
            FROM `order`
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ';

        $result = $conn->executeQuery($sql, ['days' => $days])->fetchAllAssociative();

        // Fill missing days with 0
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = (new \DateTime())->modify("-$i days")->format('Y-m-d');
            $data[$date] = 0;
        }

        foreach ($result as $row) {
            $data[$row['date']] = (int) $row['count'];
        }

        return $data;
    }

    /**
     * Get orders by status
     */
    public function getOrdersByStatus(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT status, COUNT(*) as count
            FROM `order`
            GROUP BY status
        ';

        return $conn->executeQuery($sql)->fetchAllAssociative();
    }

    /**
     * Get recent orders with details
     */
    public function getRecentOrders(int $limit = 5): array
    {
        return $this->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get total revenue
     */
    public function getTotalRevenue(): int
    {
        $result = $this->createQueryBuilder('o')
            ->select('SUM(o.total) as revenue')
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }
}
