<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class OrderValidator
{
    public function validateNoteData(array $data): array
    {
        if (!isset($data['order']) || !is_array($data['order']) || empty($data['order'][0])) {
            throw new BadRequestException('Missing order data');
        }
        $orderData = $data['order'][0];

        if (!isset($orderData['orderId']) || !is_numeric($orderData['orderId'])) {
            throw new BadRequestException('Invalid or missing orderId');
        }
        $orderId = (int) $orderData['orderId'];

        if (!isset($orderData['note']) || !is_string($orderData['note']) || trim($orderData['note']) === '') {
            throw new BadRequestException('Invalid or missing note');
        }
        $note = trim($orderData['note']);

        return [
            'orderId' => $orderId,
            'note' => $note
        ];
    }
}