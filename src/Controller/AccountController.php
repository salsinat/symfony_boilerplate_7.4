<?php

namespace App\Controller;

use App\Form\ProfileType;
use App\Repository\OrderRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/mon-compte')]
#[IsGranted('ROLE_USER')]
class AccountController extends AbstractController
{
    #[Route('', name: 'app_account')]
    public function index(): Response
    {
        return $this->render('account/index.html.twig');
    }

    #[Route('/editer', name: 'app_account_edit')]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        FileUploader $fileUploader
    ): Response {
        $user = $this->getUser();
        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $pictureFile */
            $pictureFile = $form->get('profilePictureFile')->getData();

            if ($pictureFile) {
                // If user already has a picture, we could remove it here but let's keep it simple
                $pictureFilename = $fileUploader->upload($pictureFile, 'profiles');
                $user->setProfilePicture($pictureFilename);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');

            return $this->redirectToRoute('app_account');
        }

        return $this->render('account/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/commandes', name: 'app_account_orders')]
    public function orders(OrderRepository $orderRepository): Response
    {
        $orders = $orderRepository->findByUser($this->getUser());

        return $this->render('account/orders.html.twig', [
            'orders' => $orders,
        ]);
    }
}
