<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductDetailsType;
use App\Form\ProductLicenceType;
use App\Form\ProductLogisticsType;
use App\Form\ProductTypeType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/product-flow')]
#[IsGranted('ROLE_MANAGER')]
class ProductFlowController extends AbstractController
{
    #[Route('/start', name: 'product_flow_start')]
    public function start(Request $request): Response
    {
        $session = $request->getSession();
        $product = new Product();

        // Restore from session if exists (optional, keeping it simple for now)

        $form = $this->createForm(ProductTypeType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $session->set('flow_product_type', $product->getType());
            return $this->redirectToRoute('product_flow_details');
        }

        return $this->render('product_flow/step1.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/details', name: 'product_flow_details')]
    public function details(Request $request): Response
    {
        $session = $request->getSession();
        if (!$session->has('flow_product_type')) {
            return $this->redirectToRoute('product_flow_start');
        }

        $formData = $session->get('flow_product_details', []);

        // Manually map session data to form if needed, or just bind request
        // Using a temporary product object to bind form
        $product = new Product();
        if (!empty($formData)) {
            $product->setName($formData['name'] ?? null);
            $product->setDescription($formData['description'] ?? null);
            // Convert price from cents to euros for display
            $product->setPrice(($formData['price'] ?? 0) / 100);
            $product->setStock($formData['stock'] ?? 0);
        }

        $form = $this->createForm(ProductDetailsType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Convert price from euros to cents for storage
            $priceInCents = (int) round($product->getPrice() * 100);
            $session->set('flow_product_details', [
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'price' => $priceInCents,
                'stock' => $product->getStock(),
            ]);
            return $this->redirectToRoute('product_flow_specifics');
        }

        return $this->render('product_flow/step2.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/specifics', name: 'product_flow_specifics')]
    public function specifics(Request $request): Response
    {
        $session = $request->getSession();
        $type = $session->get('flow_product_type');

        if (!$type) {
            return $this->redirectToRoute('product_flow_start');
        }

        $product = new Product();
        $specificsData = $session->get('flow_product_specifics', []);

        if ($type === 'physical') {
            $product->setWeight($specificsData['weight'] ?? null);
            $form = $this->createForm(ProductLogisticsType::class, $product);
        } else {
            $product->setLicenceKey($specificsData['licenceKey'] ?? null);
            $form = $this->createForm(ProductLicenceType::class, $product);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = [];
            if ($type === 'physical') {
                $data['weight'] = $product->getWeight();
            } else {
                $data['licenceKey'] = $product->getLicenceKey();
            }
            $session->set('flow_product_specifics', $data);
            return $this->redirectToRoute('product_flow_summary');
        }

        return $this->render('product_flow/step3.html.twig', [
            'form' => $form->createView(),
            'type' => $type
        ]);
    }

    #[Route('/summary', name: 'product_flow_summary')]
    public function summary(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $session = $request->getSession();
        $type = $session->get('flow_product_type');
        $details = $session->get('flow_product_details');
        $specifics = $session->get('flow_product_specifics');

        if (!$type || !$details) {
            return $this->redirectToRoute('product_flow_start');
        }

        $form = $this->createForm(\App\Form\ProductSummaryType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product = new Product();
            $product->setType($type);
            $product->setName($details['name']);
            $product->setSlug(strtolower($slugger->slug($details['name'])));
            $product->setDescription($details['description']);
            $product->setPrice($details['price']);
            $product->setStock($details['stock']);

            if ($type === 'physical') {
                $product->setWeight($specifics['weight']);
            } else {
                $product->setLicenceKey($specifics['licenceKey']);
            }

            $em->persist($product);
            $em->flush();

            // Clear session
            $session->remove('flow_product_type');
            $session->remove('flow_product_details');
            $session->remove('flow_product_specifics');

            $this->addFlash('success', 'Produit créé avec succès !');
            return $this->redirectToRoute('app_product_index');
        }

        return $this->render('product_flow/summary.html.twig', [
            'type' => $type,
            'details' => $details,
            'specifics' => $specifics,
            'form' => $form->createView(),
        ]);
    }
}
