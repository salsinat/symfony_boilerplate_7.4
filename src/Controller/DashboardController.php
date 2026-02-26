<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_MANAGER')]
final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        ProductRepository $productRepository,
        UserRepository $userRepository,
        OrderRepository $orderRepository
    ): Response {
        // Basic stats
        $ordersCount = $orderRepository->count([]);
        $productsCount = $productRepository->count([]);
        $usersCount = $userRepository->count([]);
        $totalRevenue = $orderRepository->getTotalRevenue();

        // Chart data
        $ordersPerDay = $orderRepository->getOrdersPerDay(7);
        $productsByType = $productRepository->getCountByType();
        $ordersByStatus = $orderRepository->getOrdersByStatus();

        // Recent orders
        $recentOrders = $orderRepository->getRecentOrders(5);

        // Low stock products
        $lowStockProducts = $productRepository->getLowStockProducts(5);

        return $this->render('dashboard/index.html.twig', [
            'orders_count' => $ordersCount,
            'products_count' => $productsCount,
            'users_count' => $usersCount,
            'total_revenue' => $totalRevenue,
            'orders_per_day' => $ordersPerDay,
            'products_by_type' => $productsByType,
            'orders_by_status' => $ordersByStatus,
            'recent_orders' => $recentOrders,
            'low_stock_products' => $lowStockProducts,
        ]);
    }
}
