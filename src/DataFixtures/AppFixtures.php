<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    private function loadDefaultUsers(ObjectManager $manager)
    {
        $admin = new User();
        $admin->setUsername('admin');
        $admin->setEmail('admin@example.com');
        $admin->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $admin->setName('John');
        $admin->setSurname('Kowalsky');
        $password = $this->encoder->encodePassword($admin, 'lajka1');
        $admin->setPassword($password);

        $user = new User();
        $user->setUsername('redactor');
        $user->setEmail('redactor@example.com');
        $user->setRoles(['ROLE_USER']);
        $user->setName('Ada');
        $user->setSurname('Malinov');
        $password = $this->encoder->encodePassword($user, 'felicette2');
        $user->setPassword($password);

        $manager->persist($admin);
        $manager->persist($user);
        $manager->flush();
    }

    private function loadDefaultCategories(ObjectManager $manager)
    {
        $categories = [
            [
                "name" => "Good news",
                "description" => "Only good news"
            ],
            [
                "name" => "Bad news",
                "description" => "Only bad news"
            ]
        ];

        foreach ($categories as $category) {
            $entity = new Category();
            $entity->setName($category["name"]);
            $entity->setDescription($category["description"]);
            $manager->persist($entity);
        }
        $manager->flush();
    }

    public function load(ObjectManager $manager)
    {
        $this->loadDefaultUsers($manager);
        $this->loadDefaultCategories($manager);
    }
}