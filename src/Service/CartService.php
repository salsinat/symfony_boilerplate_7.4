<?php

namespace App\Service;

use App\Entity\Product;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    private const CART_KEY = 'cart';

    public function __construct(
        private RequestStack $requestStack
    ) {
    }

    private function getSession()
    {
        return $this->requestStack->getSession();
    }

    public function getCart(): array
    {
        return $this->getSession()->get(self::CART_KEY, []);
    }

    public function add(Product $product, int $quantity = 1): void
    {
        $cart = $this->getCart();
        $id = $product->getId();

        if (isset($cart[$id])) {
            $cart[$id]['quantity'] += $quantity;
        } else {
            $cart[$id] = [
                'product_id' => $id,
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'quantity' => $quantity,
                'slug' => $product->getSlug(),
            ];
        }

        $this->getSession()->set(self::CART_KEY, $cart);
    }

    public function remove(int $productId): void
    {
        $cart = $this->getCart();
        unset($cart[$productId]);
        $this->getSession()->set(self::CART_KEY, $cart);
    }

    public function updateQuantity(int $productId, int $quantity): void
    {
        $cart = $this->getCart();
        if (isset($cart[$productId])) {
            if ($quantity <= 0) {
                unset($cart[$productId]);
            } else {
                $cart[$productId]['quantity'] = $quantity;
            }
            $this->getSession()->set(self::CART_KEY, $cart);
        }
    }

    public function clear(): void
    {
        $this->getSession()->remove(self::CART_KEY);
    }

    public function getTotal(): int
    {
        $total = 0;
        foreach ($this->getCart() as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        return $total;
    }

    public function getCount(): int
    {
        $count = 0;
        foreach ($this->getCart() as $item) {
            $count += $item['quantity'];
        }
        return $count;
    }
}
