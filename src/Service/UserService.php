<?php

namespace App\Service;

use App\Entity\User;
use App\Exception\ConflictException;
use App\Exception\ValidationException;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function register(array $data): array
    {
        if (empty($data['email']) || empty($data['password'])) {
            throw new ValidationException('Email and password are required');
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Invalid email format');
        }

        if (strlen($data['password']) < 8) {
            throw new ValidationException('Password must be at least 8 characters');
        }

        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $data['email']]);

        if ($existingUser) {
            throw new ConflictException('Email already exists');
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setRoles(['ROLE_USER']);
        $user->setCreatedAt(new \DateTimeImmutable());

        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return ['message' => 'User registered successfully'];
    }
}