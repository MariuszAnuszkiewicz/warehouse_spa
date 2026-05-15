<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\ApiOrderController;
use App\Dto\Location\LocationDto;
use App\Dto\Order\CreateOrderItemDto;
use App\Dto\Order\CreateOrderRequestDto;
use App\Dto\Order\OrderDto;
use App\Dto\Order\UpdateOrderRequestDto;
use App\Dto\Product\ProductDto;
use App\Entity\Order;
use App\Service\LogService;
use App\Service\OrderService;
use App\Service\OrderValidator;
use App\Service\SerializeService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ApiOrderControllerTest extends TestCase
{
    private ApiOrderController $controller;
    private LogService $logService;
    private SerializeService $serializeService;
    private OrderService $orderService;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->logService       = $this->createMock(LogService::class);
        $this->serializeService = $this->createMock(SerializeService::class);
        $this->orderService     = $this->createMock(OrderService::class);
        $this->entityManager    = $this->createMock(EntityManagerInterface::class);

        $this->controller = new ApiOrderController(
            $this->logService,
            $this->serializeService,
            $this->orderService,
            $this->entityManager
        );

        $this->controller->setContainer(new Container());
    }

    // --- index ---

    public function testIndexReturnsSerializedOrders(): void
    {
        $orders = [new Order()];
        $serialized = '[{"id":1}]';

        $this->orderService->method('getAllOrders')->willReturn($orders);
        $this->serializeService->method('dataSerialize')->with($orders)->willReturn($serialized);

        $response = $this->controller->index();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('orders', $data);
        $this->assertSame($serialized, $data['orders']);
    }

    public function testIndexReturnsNotFoundWhenOrdersEmpty(): void
    {
        $this->orderService->method('getAllOrders')->willReturn([]);
        $this->logService->expects($this->once())->method('logException')
            ->with($this->isInstanceOf(\RuntimeException::class));

        $response = $this->controller->index();

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['error']);
        $this->assertSame('The orders could not be found.', $data['message']);
    }

    // --- show ---

    public function testShowReturnsSerializedOrder(): void
    {
        $order = (new Order())->setIsPick(false)->setNote('note');
        $serialized = '{"id":1}';

        $this->orderService->method('getOrder')->with(1)->willReturn($order);
        $this->serializeService->method('dataSerialize')->with($order)->willReturn($serialized);

        $response = $this->controller->show(1);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($serialized, $data['order']);
    }

    public function testShowReturnsNotFoundWhenOrderMissing(): void
    {
        $this->orderService->method('getOrder')->with(999)->willReturn(null);
        $this->logService->expects($this->once())->method('logException')
            ->with($this->isInstanceOf(\RuntimeException::class));

        $response = $this->controller->show(999);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['error']);
        $this->assertStringContainsString('999', $data['message']);
        $this->assertStringContainsString('could not be found', $data['message']);
    }

    // --- deleteOrder ---

    public function testDeleteOrderCallsDeleteSingleOrderForEachId(): void
    {
        $this->orderService->expects($this->exactly(2))->method('deleteSingleOrder');

        $request = new Request([], [], [], [], [], [],
            json_encode(['order_ids' => [1, 2]])
        );

        $response = $this->controller->deleteOrder($request);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testDeleteOrderReturnNoContentForSingleId(): void
    {
        $this->orderService->expects($this->once())->method('deleteSingleOrder')->with(5);

        $request = new Request([], [], [], [], [], [],
            json_encode(['order_ids' => [5]])
        );

        $response = $this->controller->deleteOrder($request);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    // --- deleteProduct ---

    public function testDeleteProductCallsServiceWithCorrectIds(): void
    {
        $this->orderService->expects($this->once())
            ->method('deleteProductFromTheOrder')
            ->with(5, 3);

        $request = new Request([], [], [], [], [], [],
            json_encode(['order_id' => 5])
        );
        $request->attributes->set('id', '3');

        $response = $this->controller->deleteProduct($request);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    // --- create ---

    public function testCreateReturnsCreatedWithValidData(): void
    {
        $item = new CreateOrderItemDto(true, 'Widget', 'note', 2, 10);
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        $this->serializeService->method('deserialize')->willReturn([$item]);
        $this->orderService->expects($this->once())
            ->method('create')
            ->with($this->isInstanceOf(CreateOrderRequestDto::class));

        $payload = json_encode([['name' => 'Widget', 'isPick' => true, 'note' => 'note', 'quantity' => 2, 'quantityInStock' => 10]]);
        $request = new Request([], [], [], [], [], [], $payload);

        $response = $this->controller->create($request, $validator);

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('order created successfully', $data['message']);
        $this->assertIsArray($data['data']);
    }

    public function testCreateReturns400WhenValidationFails(): void
    {
        $violation = $this->createMock(ConstraintViolationInterface::class);
        $violation->method('getPropertyPath')->willReturn('items');
        $violation->method('getMessage')->willReturn('This value should not be blank.');

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList([$violation]));

        $this->serializeService->method('deserialize')->willReturn([]);
        $this->orderService->expects($this->never())->method('create');

        $request = new Request([], [], [], [], [], [], '[]');

        $response = $this->controller->create($request, $validator);

        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('items', $data['errors']);
    }

    // --- update ---

    public function testUpdateReturnsCreatedWithValidData(): void
    {
        $orderDto = new OrderDto();
        $orderDto->orderId = 1;
        $orderDto->isPick = false;

        $productDto = new ProductDto();
        $productDto->oldProductId = 2;
        $productDto->productName = 'Widget';

        $locationDto = new LocationDto();
        $locationDto->oldProductId = 2;
        $locationDto->locationName = 'Shelf A';

        $dto = new UpdateOrderRequestDto();
        $dto->order    = [$orderDto];
        $dto->product  = [$productDto];
        $dto->location = [$locationDto];

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        $this->serializeService->method('deserialize')->willReturn($dto);
        $this->orderService->expects($this->once())
            ->method('updateOrdersTableWithRelationships')
            ->with($orderDto, $locationDto, $productDto);
        $this->entityManager->expects($this->once())->method('flush');

        $response = $this->controller->update(
            new Request([], [], [], [], [], [], '{}'),
            $validator
        );

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('order updated successfully', $data['message']);
    }

    public function testUpdateCallsServiceForEachProduct(): void
    {
        $orderDto = new OrderDto();
        $orderDto->orderId = 1;
        $orderDto->isPick = true;

        $productDto1 = new ProductDto();
        $productDto1->oldProductId = 1;
        $productDto1->productName = 'A';

        $productDto2 = new ProductDto();
        $productDto2->oldProductId = 2;
        $productDto2->productName = 'B';

        $locationDto1 = new LocationDto();
        $locationDto1->oldProductId = 1;
        $locationDto1->locationName = 'Shelf A';

        $locationDto2 = new LocationDto();
        $locationDto2->oldProductId = 2;
        $locationDto2->locationName = 'Shelf B';

        $dto = new UpdateOrderRequestDto();
        $dto->order    = [$orderDto];
        $dto->product  = [$productDto1, $productDto2];
        $dto->location = [$locationDto1, $locationDto2];

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        $this->serializeService->method('deserialize')->willReturn($dto);
        $this->orderService->expects($this->exactly(2))
            ->method('updateOrdersTableWithRelationships');
        $this->entityManager->expects($this->once())->method('flush');

        $this->controller->update(new Request([], [], [], [], [], [], '{}'), $validator);
    }

    public function testUpdateReturns400WhenValidationFails(): void
    {
        $violation = $this->createMock(ConstraintViolationInterface::class);
        $violation->method('getPropertyPath')->willReturn('product');
        $violation->method('getMessage')->willReturn('This value should not be blank.');

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList([$violation]));

        $this->serializeService->method('deserialize')->willReturn(new UpdateOrderRequestDto());
        $this->orderService->expects($this->never())->method('updateOrdersTableWithRelationships');
        $this->entityManager->expects($this->never())->method('flush');

        $response = $this->controller->update(
            new Request([], [], [], [], [], [], '{}'),
            $validator
        );

        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
    }

    // --- updateNote ---

    public function testUpdateNoteReturnsOkWithValidData(): void
    {
        $orderValidator = $this->createMock(OrderValidator::class);
        $orderValidator->method('validateNoteData')
            ->willReturn(['orderId' => 1, 'note' => 'new note']);

        $this->orderService->expects($this->once())
            ->method('updateNote')
            ->with(1, 'new note');

        $request = new Request([], [], [], [], [], [],
            json_encode(['order' => [['orderId' => 1, 'note' => 'new note']]])
        );

        $response = $this->controller->updateNote($request, $orderValidator);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Note field of order updated successfully', $data['message']);
        $this->assertSame(['orderId' => 1, 'note' => 'new note'], $data['data']);
    }

    public function testUpdateNoteReturns500WhenOrderServiceThrows(): void
    {
        $orderValidator = $this->createMock(OrderValidator::class);
        $orderValidator->method('validateNoteData')
            ->willReturn(['orderId' => 1, 'note' => 'note']);

        $this->orderService->method('updateNote')
            ->willThrowException(new \RuntimeException('Order not found: 1'));

        $request = new Request([], [], [], [], [], [],
            json_encode(['order' => [['orderId' => 1, 'note' => 'note']]])
        );

        $response = $this->controller->updateNote($request, $orderValidator);

        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('Order not found', $data['error']);
    }
}
