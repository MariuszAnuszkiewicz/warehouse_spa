<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\SerializeService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class SerializeServiceTest extends TestCase
{
    private SerializerInterface $serializerMock;
    private SerializeService $service;

    protected function setUp(): void
    {
        $this->serializerMock = $this->createMock(SerializerInterface::class);

        $this->service = new SerializeService(
            $this->serializerMock
        );
    }
    public function testDataSerializeWithArray(): void
    {
        $data = ['id' => '123'];

        $this->serializerMock
            ->expects($this->once())
            ->method('serialize')
            ->with(
                $data,
                'json',
                $this->callback(function ($context) {
                    return isset($context[AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER])
                        && is_callable($context[AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER]);
                })
            )
            ->willReturn('{"id":"123"}');

        $result = $this->service->dataSerialize($data);

        $this->assertSame('{"id":"123"}', $result);
    }

    public function testDataSerializeWithObject(): void
    {
        $object = new class() {
            public function getId(): int { return 123; }
        };

        $this->serializerMock
            ->expects($this->once())
            ->method('serialize')
            ->with(
                $object,
                'json',
                $this->callback(function ($context) use ($object) {
                    $handler = $context[AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER];
                    $result = $handler($object);
                    return is_array($result) && $result[0] === $object;
                })
            )
            ->willReturn('{"id":123}');

        $result = $this->service->dataSerialize($object);

        $this->assertSame('{"id":123}', $result);
    }
}