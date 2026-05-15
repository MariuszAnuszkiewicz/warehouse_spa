<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\SerializeService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class SerializeServiceTest extends TestCase
{
    private SerializerInterface $serializer;
    private SerializeService $service;

    protected function setUp(): void
    {
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->service    = new SerializeService($this->serializer);
    }

    // --- dataSerialize ---

    public function testDataSerializePassesArrayToSerializer(): void
    {
        $data = ['id' => 1, 'name' => 'Widget'];

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with(
                $data,
                'json',
                $this->callback(fn($ctx) =>
                    isset($ctx[AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER]) &&
                    is_callable($ctx[AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER])
                )
            )
            ->willReturn('{"id":1,"name":"Widget"}');

        $result = $this->service->dataSerialize($data);

        $this->assertSame('{"id":1,"name":"Widget"}', $result);
    }

    public function testDataSerializePassesObjectToSerializer(): void
    {
        $object = new class { public function getId(): int { return 5; } };

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($object, 'json', $this->isType('array'))
            ->willReturn('{"id":5}');

        $this->assertSame('{"id":5}', $this->service->dataSerialize($object));
    }

    public function testCircularReferenceHandlerReturnsArrayWhenIdIsTruthy(): void
    {
        $object = new class { public function getId(): int { return 1; } };

        $capturedContext = null;
        $this->serializer->method('serialize')
            ->willReturnCallback(function ($data, $format, $context) use (&$capturedContext) {
                $capturedContext = $context;
                return '{}';
            });

        $this->service->dataSerialize($object);

        $handler = $capturedContext[AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER];
        $this->assertSame([$object], $handler($object));
    }

    public function testCircularReferenceHandlerReturnsEmptyStringWhenIdIsFalsy(): void
    {
        $object = new class { public function getId(): int { return 0; } };

        $capturedContext = null;
        $this->serializer->method('serialize')
            ->willReturnCallback(function ($data, $format, $context) use (&$capturedContext) {
                $capturedContext = $context;
                return '{}';
            });

        $this->service->dataSerialize($object);

        $handler = $capturedContext[AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER];
        $this->assertSame('', $handler($object));
    }

    // --- deserialize ---

    public function testDeserializePassesCorrectArgumentsToSerializer(): void
    {
        $json     = '{"name":"Widget","isPick":true}';
        $dtoClass = 'App\Dto\Order\CreateOrderItemDto';
        $expected = new \stdClass();

        $this->serializer->expects($this->once())
            ->method('deserialize')
            ->with($json, $dtoClass, 'json')
            ->willReturn($expected);

        $result = $this->service->deserialize($json, $dtoClass);

        $this->assertSame($expected, $result);
    }

    public function testDeserializeReturnsArray(): void
    {
        $json     = '[{"name":"A"},{"name":"B"}]';
        $dtoClass = 'App\Dto\Order\CreateOrderItemDto[]';
        $expected = [new \stdClass(), new \stdClass()];

        $this->serializer->method('deserialize')->willReturn($expected);

        $result = $this->service->deserialize($json, $dtoClass);

        $this->assertSame($expected, $result);
    }
}
