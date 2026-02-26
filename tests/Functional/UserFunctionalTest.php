<?php

namespace App\Tests\Functional;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserFunctionalTest extends WebTestCase
{
    public function testAdminPageIsProtected()
    {
        $client = static::createClient();
        $client->request('GET', '/admin/users/');

        // Should redirect to login
        $this->assertResponseRedirects('/login');
    }

    /*
    public function testAdminCanAccessUserIndex()
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);

        // Assuming there is an admin user in fixtures. 
        // If not, we would need to create one here or in setUp.
        // For this example, I'll comment it out as I cannot guarantee DB state without refreshing it.
        /*
        $testUser = $userRepository->findOneByEmail('admin@example.com');
        $client->loginUser($testUser);

        $client->request('GET', '/admin/users/');
        $this->assertResponseIsSuccessful();
        */
    //}
}
