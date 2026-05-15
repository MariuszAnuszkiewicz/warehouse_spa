<?php

namespace App\Controller\auth;

use App\Dto\RegisterUserDTO;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api', name: 'app_api_auth')]
class ApiAuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $jwtManager,
        private ValidatorInterface $validator,
        private RefreshTokenManagerInterface $refreshTokenManager,
        private SerializerInterface $serializer
    ) {}

    /*
     * Generate token which we use to authenticate and access to endpoints of application
     */
    #[Route('/login', name: '_login',  methods: ['POST'])]
    public function login(): void
    {
        throw new \RuntimeException('This method should not be called directly.');
    }

    #[Route('/register', name: '_register', methods: ['GET','POST'])]
    public function register(Request $request): JsonResponse
    {
        $dto = $this->serializer->deserialize($request->getContent(), RegisterUserDTO::class, 'json');

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()][] = $error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setEmail($dto->email);
        $user->setName($dto->name);
        $user->setPassword($this->passwordHasher->hashPassword($user, $dto->password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'User registered',
            'user' => ['id' => $user->getId(), 'email' => $user->getEmail(), 'name' => $user->getName()]
        ], Response::HTTP_CREATED);
    }

    #[Route('/token/refresh', name: '_refresh_token', methods: ['POST'])]
    public function refreshToken(Request $request, UserRepository $userRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $refreshTokenString = $data['refresh_token'] ?? null;

        if (!$refreshTokenString) {
            return $this->json(['error' => 'No refresh token provided'], Response::HTTP_BAD_REQUEST);
        }

        $refreshToken = $this->refreshTokenManager->get($refreshTokenString);
        if (!$refreshToken || !$refreshToken->isValid()) {
            return $this->json(['error' => 'Invalid refresh token'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $userRepository->findOneBy(['email' => $refreshToken->getUsername()]);
        $newToken = $this->jwtManager->create($user);

        return $this->json([
            'token' => $newToken,
            'refresh_token' => $refreshTokenString
        ]);
    }
}