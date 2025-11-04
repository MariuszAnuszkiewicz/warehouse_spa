<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Auth;

use App\Controller\auth\ApiAuthController;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ApiAuthControllerTest extends WebTestCase
{
    private $apiAuthController;
    private $client;
    private $controller;
    private $mockEntityManager;
    private $mockJwtManager;
    private $mockPasswordHasher;
    private $mockRefreshTokenManager;
    private $mockValidator;

    protected function setUp(): void
    {
        $this->mockValidator = $this->createMock(ValidatorInterface::class);
        $this->mockEntityManager = $this->createMock(EntityManagerInterface::class);
        $this->mockPasswordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->mockJwtManager = $this->createMock(JWTTokenManagerInterface::class);
        $this->mockRefreshTokenManager = $this->createMock(RefreshTokenManagerInterface::class);

        $this->apiAuthController = new ApiAuthController(
            $this->mockEntityManager,
            $this->mockPasswordHasher,
            $this->mockJwtManager,
            $this->mockValidator,
            $this->mockRefreshTokenManager
        );

        $this->client = static::createClient();
        $this->container = static::getContainer();
        $this->controller = self::getContainer()->get(ApiAuthController::class);

        $this->entityManager = $this->container->get('doctrine')->getManager();
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'test@example.com']);

        if ($user?->getEmail() === 'test@example.com') {
            $this->entityManager->remove($user);
        } else {
            $this->entityManager->flush();
        }
    }

    public function  testRegisterSuccess(): void
    {
        $inputData = [
            'email' => 'test@example.com',
            'name' => 'Mariusz',
            'password' => 'password123',
        ];

        $this->client->request(
            'POST',
            '/api/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($inputData)
        );

        $response = $this->client->getResponse();

        $data = json_decode($response->getContent(), true);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('User registered', $data['message']);
        $this->assertEquals('test@example.com', $data['user']['email']);
        $this->assertEquals('Mariusz', $data['user']['name']);

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'test@example.com']);
        $this->assertNotNull($user);
    }

    public function testRegisterValidationError()
    {
        $inputData = [
            'email' => 'invalid-email',
            'name' => 'Mariusz',
            'password' => 'password123'
        ];

        $request = $this->createMock(Request::class);
        $request->method('getContent')->willReturn(json_encode($inputData));

        $violations = new ConstraintViolationList([
            new ConstraintViolation('Email is required', null, [], 'invalid-email', 'email', null),
            new ConstraintViolation('Name is required', null, [], 'Mariusz', 'name', null),
            new ConstraintViolation('Password is required', null, [], 'password123', 'password', null),
        ]);

        $violation = $this->createMock(ConstraintViolation::class);
        $violation->method('getPropertyPath')->willReturn('email');
        $violation->method('getMessage')->willReturn('Invalid email');

        $violation = $this->createMock(ConstraintViolationListInterface::class);
        $violation->method('count')->willReturn(1);

        $this->mockValidator->method('validate')->willReturn($violations);

        $response = $this->controller->register($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(JsonResponse::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertArrayHasKey('email',  $responseData['errors']);
        $this->assertEquals('Invalid email format', $responseData['errors']['email'][0]);
    }

    public function testLoginSuccess(): void
    {
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'test@example.com',
                'password' => 'password123'
            ])
        );

        $response = $this->client->getResponse();

        $this->assertResponseStatusCodeSame(200);

        $this->assertJson($response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('token', $data);
        $this->assertArrayHasKey('refresh_token', $data);

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+$/', $data['token']);
        $this->assertNotEmpty($data['refresh_token']);
    }

    public function testLoginMethodThrowsExceptionIfCalledDirectly(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('This method should not be called directly.');

        $this->controller->login();
    }
}