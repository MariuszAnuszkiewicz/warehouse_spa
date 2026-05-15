<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Auth;

use App\Controller\auth\ApiAuthController;
use App\Dto\RegisterUserDTO;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ApiAuthControllerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private JWTTokenManagerInterface $jwtManager;
    private ValidatorInterface $validator;
    private RefreshTokenManagerInterface $refreshTokenManager;
    private SerializerInterface $serializer;
    private ApiAuthController $controller;

    protected function setUp(): void
    {
        $this->entityManager       = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher      = $this->createMock(UserPasswordHasherInterface::class);
        $this->jwtManager          = $this->createMock(JWTTokenManagerInterface::class);
        $this->validator           = $this->createMock(ValidatorInterface::class);
        $this->refreshTokenManager = $this->createMock(RefreshTokenManagerInterface::class);
        $this->serializer          = $this->createMock(SerializerInterface::class);

        $this->controller = new ApiAuthController(
            $this->entityManager,
            $this->passwordHasher,
            $this->jwtManager,
            $this->validator,
            $this->refreshTokenManager,
            $this->serializer
        );

        $this->controller->setContainer(new Container());
    }

    // --- login ---

    public function testLoginThrowsRuntimeException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('This method should not be called directly.');

        $this->controller->login();
    }

    // --- register ---

    public function testRegisterReturnsCreatedWithValidData(): void
    {
        $dto = new RegisterUserDTO();
        $dto->email    = 'test@example.com';
        $dto->name     = 'Mariusz';
        $dto->password = 'password123';

        $this->serializer->method('deserialize')->willReturn($dto);
        $this->validator->method('validate')->willReturn(new ConstraintViolationList());
        $this->passwordHasher->method('hashPassword')->willReturn('hashed_pw');
        $this->entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(User::class));
        $this->entityManager->expects($this->once())->method('flush');

        $request = new Request([], [], [], [], [], [],
            json_encode(['email' => $dto->email, 'name' => $dto->name, 'password' => $dto->password])
        );

        $response = $this->controller->register($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('User registered', $data['message']);
        $this->assertSame($dto->email, $data['user']['email']);
        $this->assertSame($dto->name, $data['user']['name']);
    }

    public function testRegisterHashesPasswordBeforePersist(): void
    {
        $dto = new RegisterUserDTO();
        $dto->email    = 'hash@example.com';
        $dto->name     = 'Test';
        $dto->password = 'plaintext';

        $this->serializer->method('deserialize')->willReturn($dto);
        $this->validator->method('validate')->willReturn(new ConstraintViolationList());
        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->with($this->isInstanceOf(User::class), 'plaintext')
            ->willReturn('$2y$hashed');

        $this->entityManager->method('persist');
        $this->entityManager->method('flush');

        $this->controller->register(new Request([], [], [], [], [], [], json_encode([])));
    }

    public function testRegisterReturns400WhenValidationFails(): void
    {
        $dto = new RegisterUserDTO();
        $dto->email    = '';
        $dto->name     = '';
        $dto->password = '';

        $this->serializer->method('deserialize')->willReturn($dto);

        $violation = new ConstraintViolation('Email is required', null, [], null, 'email', '');
        $this->validator->method('validate')->willReturn(new ConstraintViolationList([$violation]));

        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->never())->method('flush');

        $response = $this->controller->register(new Request([], [], [], [], [], [], '{}'));

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('email', $data['errors']);
        $this->assertSame(['Email is required'], $data['errors']['email']);
    }

    public function testRegisterGroupsMultipleViolationsPerField(): void
    {
        $dto = new RegisterUserDTO();
        $dto->email = $dto->name = $dto->password = '';

        $this->serializer->method('deserialize')->willReturn($dto);

        $violations = new ConstraintViolationList([
            new ConstraintViolation('Email is required',  null, [], null, 'email', ''),
            new ConstraintViolation('Invalid email format', null, [], null, 'email', ''),
            new ConstraintViolation('Password is required', null, [], null, 'password', ''),
        ]);
        $this->validator->method('validate')->willReturn($violations);

        $response = $this->controller->register(new Request([], [], [], [], [], [], '{}'));

        $data = json_decode($response->getContent(), true);
        $this->assertCount(2, $data['errors']['email']);
        $this->assertCount(1, $data['errors']['password']);
    }

    // --- refreshToken ---

    public function testRefreshTokenReturns400WhenTokenMissing(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([]));

        $response = $this->controller->refreshToken($request, $this->createMock(UserRepository::class));

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('No refresh token provided', $data['error']);
    }

    public function testRefreshTokenReturns401WhenTokenNotFound(): void
    {
        $this->refreshTokenManager->method('get')->willReturn(null);

        $request = new Request([], [], [], [], [], [],
            json_encode(['refresh_token' => 'invalid-token'])
        );

        $response = $this->controller->refreshToken($request, $this->createMock(UserRepository::class));

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Invalid refresh token', $data['error']);
    }

    public function testRefreshTokenReturns401WhenTokenExpired(): void
    {
        $refreshToken = $this->createMock(RefreshTokenInterface::class);
        $refreshToken->method('isValid')->willReturn(false);

        $this->refreshTokenManager->method('get')->willReturn($refreshToken);

        $request = new Request([], [], [], [], [], [],
            json_encode(['refresh_token' => 'expired-token'])
        );

        $response = $this->controller->refreshToken($request, $this->createMock(UserRepository::class));

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Invalid refresh token', $data['error']);
    }

    public function testRefreshTokenReturnsNewTokenWhenValid(): void
    {
        $tokenString = 'valid-refresh-token-abc123';
        $newJwt      = 'new.jwt.token';
        $user        = new User();

        $refreshToken = $this->createMock(RefreshTokenInterface::class);
        $refreshToken->method('isValid')->willReturn(true);
        $refreshToken->method('getUsername')->willReturn('user@example.com');

        $this->refreshTokenManager->method('get')->with($tokenString)->willReturn($refreshToken);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->with(['email' => 'user@example.com'])->willReturn($user);

        $this->jwtManager->expects($this->once())->method('create')->with($user)->willReturn($newJwt);

        $request = new Request([], [], [], [], [], [],
            json_encode(['refresh_token' => $tokenString])
        );

        $response = $this->controller->refreshToken($request, $userRepository);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($newJwt, $data['token']);
        $this->assertSame($tokenString, $data['refresh_token']);
    }
}
