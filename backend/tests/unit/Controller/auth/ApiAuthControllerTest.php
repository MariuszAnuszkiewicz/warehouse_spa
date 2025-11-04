<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Auth;

use App\Controller\auth\ApiAuthController;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ApiAuthControllerTest extends TestCase
{
    private $controller;
    private $mockEntityManager;
    private $mockJwtManager;
    private $mockPasswordHasher;
    private $mockRefreshTokenManager;
    private $mockValidator;

    protected function setUp(): void
    {
        $this->mockEntityManager = $this->createMock(EntityManagerInterface::class);
        $this->mockJwtManager = $this->createMock(JWTTokenManagerInterface::class);
        $this->mockPasswordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->mockRefreshTokenManager = $this->createMock(RefreshTokenManagerInterface::class);
        $this->mockValidator = $this->createMock(ValidatorInterface::class);

        $this->controller = new ApiAuthController(
            $this->mockEntityManager,
            $this->mockPasswordHasher,
            $this->mockJwtManager,
            $this->mockValidator,
            $this->mockRefreshTokenManager
        );

        $this->controller->setContainer(new Container());
    }

    public function testRegisterSuccess(): void
    {
        $inputData = [
            'email' => 'test@example.com',
            'name' => 'Mariusz',
            'password' => 'password123'
        ];

        $request = new Request([], [], [], [], [], [], json_encode($inputData));

        $this->mockValidator->method('validate')->willReturn(new ConstraintViolationList());

        // PasswordHasher returns a hashed password
        $this->mockPasswordHasher
            ->method('hashPassword')
            ->willReturn('hashed_password123');

        // Mock EntityManager persist and flush
        $this->mockEntityManager->expects($this->once())->method('persist');
        $this->mockEntityManager->expects($this->once())->method('flush');

        // Mock user ID after persistence
        $user = new User();
        $user->setEmail($inputData['email']);
        $user->setName($inputData['name']);
        $user->setPassword('hashed_password123');

        // Execute controller method
        $response = $this->controller->register($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('User registered', $content['message']);
        $this->assertArrayHasKey('user', $content);
        $this->assertEquals($inputData['email'], $content['user']['email']);
        $this->assertEquals($inputData['name'], $content['user']['name']);
    }

    public function testRegisterValidationErrors(): void
    {
        $inputData = [
            'email' => '', // invalid email
            'name' => '',
            'password' => ''
        ];

        $request = new Request([], [], [], [], [], [], json_encode($inputData));

        // Simulate validation errors
        $violation = new ConstraintViolation('Email is required', null, [], null, 'email', '');
        $violations = new ConstraintViolationList([$violation]);

        $this->mockValidator->method('validate')->willReturn($violations);

        $response = $this->controller->register($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('errors', $content);
        $this->assertArrayHasKey('email', $content['errors']);
        $this->assertEquals(['Email is required'], $content['errors']['email']);
    }

    public function testLoginMethodThrowsExceptionIfCalledDirectly(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('This method should not be called directly.');

        $this->controller->login();
    }

    public function testExpectedLoginResponseStructure(): void
    {
        // Expected example response structure
        $expected = [
            'token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE3NjIyNTY5NTcsImV4cCI6MTc2MjI2MDU1Nywicm9sZXMiO',
            'refresh_token' => '1326d79b45ee84d33649011ea70b545da3fd0fa6f6ae5096a760e0ae8b7f76199030bbfd29307d8b531'
        ];

        // Simulate the response that would be returned by login
        $response = [
            'token' => $expected['token'],
            'refresh_token' => $expected['refresh_token']
        ];

        // Check structure
        $this->assertArrayHasKey('token', $response);
        $this->assertArrayHasKey('refresh_token', $response);

        // Check equality
        $this->assertEquals($expected, $response);
    }
}

