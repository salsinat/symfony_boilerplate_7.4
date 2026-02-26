<?php

namespace App\DataFixtures;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private SluggerInterface $slugger
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Disable SQL logging for performance
        $manager->getConnection()->getConfiguration()->setMiddlewares([]);



        // Create admin
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setFirstname('Admin');
        $admin->setLastname('System');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setIsVerified(true);
        $admin->setAddress('1 Rue Admin, 75001 Paris');
        $manager->persist($admin);

        // Create 100 users
        $users = [$admin];
        $firstNames = ['Jean', 'Marie', 'Pierre', 'Sophie', 'Nicolas', 'Julie', 'Thomas', 'Camille', 'Alexandre', 'Emma'];
        $lastNames = ['Martin', 'Bernard', 'Dubois', 'Thomas', 'Robert', 'Richard', 'Petit', 'Durand', 'Leroy', 'Moreau'];

        for ($i = 1; $i <= 100; $i++) {
            $user = new User();
            $user->setEmail("user{$i}@example.com");
            $user->setFirstname($firstNames[$i % 10]);
            $user->setLastname($lastNames[$i % 10]);
            $user->setRoles($i <= 5 ? ['ROLE_MANAGER'] : ['ROLE_USER']);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
            $user->setIsVerified($i <= 80);
            $user->setAddress("{$i} Rue Test, 75001 Paris");
            $manager->persist($user);
            $users[] = $user;
        }
        $manager->flush();
        echo "✓ 101 utilisateurs créés\n";

        // Create 400 products
        $products = [];
        $physicalNames = ['Casque Audio', 'Souris Gaming', 'Clavier', 'Écran', 'Webcam', 'Micro', 'Hub USB', 'Support', 'Tapis', 'Chaise'];
        $digitalNames = ['Licence Windows', 'Office 365', 'Antivirus', 'VPN', 'Formation', 'E-book', 'Pack Icons', 'Template', 'Plugin', 'Licence Pro'];

        for ($i = 0; $i < 200; $i++) {
            $name = $physicalNames[$i % 10] . ' Pro ' . ($i + 1);
            $product = new Product();
            $product->setName($name);
            $product->setSlug('product-physical-' . $i);
            $product->setDescription('Produit physique de haute qualité.');
            $product->setPrice(random_int(999, 49999));
            $product->setStock(random_int(0, 100));
            $product->setType('physical');
            $product->setWeight(round(random_int(100, 2000) / 1000, 2));
            $manager->persist($product);
            $products[] = $product;
        }

        for ($i = 0; $i < 200; $i++) {
            $name = $digitalNames[$i % 10] . ' Edition ' . ($i + 1);
            $product = new Product();
            $product->setName($name);
            $product->setSlug('product-digital-' . $i);
            $product->setDescription('Produit numérique avec licence.');
            $product->setPrice(random_int(499, 29999));
            $product->setStock(random_int(50, 999));
            $product->setType('digital');
            $product->setLicenceKey('LIC-' . strtoupper(substr(md5((string) $i), 0, 16)));
            $manager->persist($product);
            $products[] = $product;
        }
        $manager->flush();
        echo "✓ 400 produits créés\n";

        // Create 1000 orders (optimized)
        $statuses = [Order::STATUS_PENDING, Order::STATUS_PAID, Order::STATUS_SHIPPED, Order::STATUS_DELIVERED, Order::STATUS_CANCELLED];
        $addresses = ['1 Rue de Paris, 75001 Paris', '2 Avenue Lyon, 69001 Lyon', '3 Boulevard Marseille, 13001 Marseille'];

        for ($batch = 0; $batch < 2; $batch++) {
            for ($i = 0; $i < 100; $i++) {
                $order = new Order();
                $order->setUser($users[random_int(1, 100)]);
                $order->setStatus($statuses[random_int(0, 4)]);
                $order->setShippingAddress($addresses[random_int(0, 2)]);

                // Set random date in last 30 days
                $daysAgo = random_int(0, 30);
                $reflection = new \ReflectionClass($order);
                $property = $reflection->getProperty('createdAt');
                $property->setValue($order, new \DateTimeImmutable("-{$daysAgo} days"));

                // Add 1-3 items
                $itemCount = random_int(1, 3);
                for ($j = 0; $j < $itemCount; $j++) {
                    $product = $products[random_int(0, 399)];
                    $item = new OrderItem();
                    $item->setProduct($product);
                    $item->setQuantity(random_int(1, 2));
                    $item->setPrice($product->getPrice());
                    $order->addItem($item);
                    $manager->persist($item);
                }
                $order->calculateTotal();
                $manager->persist($order);
            }
            $manager->flush();
            $manager->clear();

            // Reload users and products references
            $users = $manager->getRepository(User::class)->findAll();
            $products = $manager->getRepository(Product::class)->findAll();

            echo "✓ " . (($batch + 1) * 100) . " commandes créées\n";
        }

        echo "\n✅ Fixtures terminées !\n";
        echo "Admin: admin@example.com / admin123\n";
    }
}
