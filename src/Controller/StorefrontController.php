<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StorefrontController extends AbstractController
{
    #[Route('/', name: 'app_storefront_home')]
    public function home(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findBy(['stock' => [1, 100]], ['id' => 'DESC'], 8);

        // Get all products if none with stock filter
        if (empty($products)) {
            $products = $productRepository->findBy([], ['id' => 'DESC'], 8);
        }

        return $this->render('storefront/home.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/catalogue', name: 'app_storefront_catalog')]
    public function catalog(Request $request, ProductRepository $productRepository): Response
    {
        $type = $request->query->get('type');

        $criteria = [];
        if ($type && in_array($type, ['physical', 'digital'])) {
            $criteria['type'] = $type;
        }

        $products = $productRepository->findBy($criteria, ['id' => 'DESC']);

        return $this->render('storefront/catalog.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/produit/{slug}', name: 'app_storefront_product')]
    public function product(string $slug, ProductRepository $productRepository): Response
    {
        $product = $productRepository->findOneBy(['slug' => $slug]);

        if (!$product) {
            throw $this->createNotFoundException('Produit non trouvé');
        }

        return $this->render('storefront/product.html.twig', [
            'product' => $product,
        ]);
    }
}
