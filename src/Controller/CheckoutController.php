<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\ProductRepository;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/checkout')]
class CheckoutController extends AbstractController
{
    public function __construct(
        private CartService $cartService,
        private ProductRepository $productRepository
    ) {
    }

    #[Route('', name: 'app_checkout')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        $cart = $this->cartService->getCart();

        if (empty($cart)) {
            return $this->redirectToRoute('app_cart');
        }

        $user = $this->getUser();

        return $this->render('storefront/checkout.html.twig', [
            'cart' => $cart,
            'total' => $this->cartService->getTotal(),
            'user' => $user,
        ]);
    }

    #[Route('/confirmer', name: 'app_checkout_confirm', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function confirm(Request $request, EntityManagerInterface $em): Response
    {
        $cart = $this->cartService->getCart();

        if (empty($cart)) {
            return $this->redirectToRoute('app_cart');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $address = $request->request->get('address');

        // Update user address if provided
        if ($address) {
            $user->setAddress($address);
        }

        // Create order
        $order = new Order();
        $order->setUser($user);
        $order->setShippingAddress($address ?: $user->getAddress());
        $order->setStatus(Order::STATUS_PENDING);

        // Add items
        foreach ($cart as $id => $item) {
            $product = $this->productRepository->find($id);
            if ($product) {
                $orderItem = new OrderItem();
                $orderItem->setProduct($product);
                $orderItem->setQuantity($item['quantity']);
                $orderItem->setPrice($item['price']);
                $order->addItem($orderItem);

                // Update stock
                $product->setStock($product->getStock() - $item['quantity']);
            }
        }

        $order->calculateTotal();

        $em->persist($order);
        $em->flush();

        // Clear cart
        $this->cartService->clear();

        $this->addFlash('success', 'Votre commande a été passée avec succès !');

        return $this->redirectToRoute('app_order_confirmation', ['id' => $order->getId()]);
    }

    #[Route('/confirmation/{id}', name: 'app_order_confirmation')]
    #[IsGranted('ROLE_USER')]
    public function confirmation(Order $order): Response
    {
        // Ensure user owns this order
        if ($order->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('storefront/order_confirmation.html.twig', [
            'order' => $order,
        ]);
    }
}
