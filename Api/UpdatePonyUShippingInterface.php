<?php
declare(strict_types=1);

namespace Emergento\PonyUApi\Api;

interface UpdatePonyUShippingInterface
{
    /**
     * @param int $customerOrderId
     * @param string $orderId
     * @param string $status
     * @param string $operationDate
     * @return void
     */
    public function execute(int $customerOrderId, string $orderId, string $status, string $operationDate): void;
}
