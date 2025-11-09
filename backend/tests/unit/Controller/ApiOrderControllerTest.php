<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\ApiOrderController;
use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\LogService;
use App\Service\OrderService;
use App\Service\SerializeService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiOrderControllerTest extends TestCase
{
    private ApiOrderController $controller;
    private LogService $mockLogService;
    private SerializeService $mockSerializeService;
    private EntityManagerInterface $mockEntityManager;
    private OrderRepository $mockOrderRepository;
    private OrderService $mockOrderService;

    protected function setUp(): void
    {
        $this->mockEntityManager = $this->createMock(EntityManagerInterface::class);
        $this->mockLogService = $this->createMock(LogService::class);
        $this->mockSerializeService = $this->createMock(SerializeService::class);
        $this->mockOrderRepository = $this->createMock(OrderRepository::class);
        $this->mockOrderService = $this->createMock(OrderService::class);

        $this->controller = new ApiOrderController(
            $this->mockLogService,
            $this->mockSerializeService,
            $this->mockOrderService
        );

        $this->controller->setContainer(new Container());
    }

    public function testIndexReturnsSerializedOrders(): void
    {
        $orders = [['id' => 1, 'quantityInOrder' => 24, 'products' => [], 'isPick' => false, 'note' => 'some note 1']];
        $serializedJson = json_encode($orders);

        $this->mockOrderService
            ->method('getAllOrders')
            ->willReturn($orders);

        $this->mockSerializeService
            ->method('dataSerialize')
            ->with($orders)
            ->willReturn($serializedJson);

        $response = $this->controller->index();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('orders', $data);
        $this->assertEquals($serializedJson, $data['orders']);
    }

    public function testIndexThrowsExceptionWhenNoOrders(): void
    {
        $this->mockOrderRepository
            ->method('ordersWithRelationships')
            ->willReturn([]);

        $this->mockLogService
            ->expects($this->once())
            ->method('logException')
            ->with($this->isInstanceOf(\RuntimeException::class));

        $response = $this->controller->index();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['error']);
        $this->assertEquals('The orders could not be found.', $data['message']);
    }

    public function testShowReturnsSerializedOrder(): void
    {
        $order = [['id' => 1, 'quantityInOrder' => 24, 'products' => [], 'isPick' => false, 'note' => 'some note 1']];
        $serializedJson = json_encode($order);
        $datetime = new \DateTime(date('Y-m-d H:i:s'));

        $orderObj = (new Order())
            ->setQuantityInOrder(24)
            ->setIsPick(false)
            ->setNote('some note 1')
            ->setCreatedAt($datetime);

        $this->mockOrderService
            ->method('getOrder')
            ->with(1)
            ->willReturn($orderObj);

        $this->mockSerializeService
            ->method('dataSerialize')
            ->with($orderObj)
            ->willReturn($serializedJson);

        $response = $this->controller->show(1);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(['order' => $serializedJson], $data);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testShowReturnsErrorIfOrderNotFound(): void
    {
        $this->mockOrderRepository
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->mockLogService
            ->expects($this->once())
            ->method('logException')
            ->with($this->isInstanceOf(\Throwable::class));

        $response = $this->controller->show(999);

        $this->assertInstanceOf(JsonResponse::class, $response);

        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['error']);
        $this->assertStringContainsString('could not be found', $data['message']);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testCreateReturnsJsonResponseWithCorrectData(): void
    {
        $order = [['id' => 1, 'quantityInOrder' => 24, 'products' => [], 'isPick' => false, 'note' => 'some note 1']];
        $serializedJson = json_encode($order);

        $request = new Request([], [], [], [], [], [], $serializedJson);

        $this->mockOrderService->expects($this->once())
            ->method('create')
            ->with($request);

        $response = $this->controller->create($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);

        $this->assertEquals('order created successfully', $data['message']);
        $this->assertEquals($serializedJson, json_encode($data['data']));
    }
}