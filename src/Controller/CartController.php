<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/panier')]
class CartController extends AbstractController
{
    public function __construct(
        private CartService $cartService
    ) {
    }

    #[Route('', name: 'app_cart')]
    public function index(): Response
    {
        return $this->render('storefront/cart.html.twig', [
            'cart' => $this->cartService->getCart(),
            'total' => $this->cartService->getTotal(),
        ]);
    }

    #[Route('/ajouter/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(int $id, ProductRepository $productRepository): Response
    {
        $product = $productRepository->find($id);

        if (!$product) {
            throw $this->createNotFoundException('Produit non trouvé');
        }

        if ($product->getStock() <= 0) {
            $this->addFlash('error', 'Ce produit est en rupture de stock');
            return $this->redirectToRoute('app_storefront_product', ['slug' => $product->getSlug()]);
        }

        $this->cartService->add($product);
        $this->addFlash('success', 'Produit ajouté au panier !');

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/supprimer/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(int $id): Response
    {
        $this->cartService->remove($id);
        $this->addFlash('success', 'Produit retiré du panier');

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/modifier/{id}', name: 'app_cart_update', methods: ['POST'])]
    public function update(int $id, Request $request): Response
    {
        $quantity = (int) $request->request->get('quantity', 1);
        $this->cartService->updateQuantity($id, $quantity);

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/vider', name: 'app_cart_clear', methods: ['POST'])]
    public function clear(): Response
    {
        $this->cartService->clear();
        $this->addFlash('success', 'Panier vidé');

        return $this->redirectToRoute('app_storefront_catalog');
    }
}
