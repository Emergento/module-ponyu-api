<?php
declare(strict_types=1);

namespace Emergento\PonyUApi\Model;

use Emergento\PonyUApi\Api\UpdatePonyUShippingInterface;
use Emergento\PonyUShippingMethod\Model\Config as PonyUConfig;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderStatusHistoryInterfaceFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Implementation of UpdatePonyUShippingInterface, update status for an order and store an history message with more information.
 * @api
 */
class UpdatePonyUShipping implements UpdatePonyUShippingInterface
{
    public function __construct(
        private readonly ManagerInterface $eventManager,
        private readonly OrderStatusHistoryInterfaceFactory $orderStatusHistoryFactory,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly LoggerInterface $logger,
        private readonly PonyUConfig $ponyUConfig
    ) {
    }

    public function execute(int $customerOrderId, string $orderId, string $status, string $operationDate): void
    {
        try {
            $order = $this->getOrderByIncrementId($orderId);

            $ponyuStatus = strtolower($status);
            $this->eventManager->dispatch(sprintf('ponyu_status_changed_%s',$ponyuStatus), ['order' => $order]);

            $orderStatusHistory = $this->orderStatusHistoryFactory->create();
            $orderStatusHistory->setComment(__($this->ponyUConfig->getMagentoStatusMessage($status)));
            $orderStatusHistory->setParentId($order->getEntityId());
            $orderStatusHistory->setStatus($order->getStatus());
            $order->addStatusHistory($orderStatusHistory);
            $this->orderRepository->save($order);
        } catch (NoSuchEntityException $e) {
            $this->logger->debug(__('invalid order id %1 : %2', $orderId, $e->getMessage()));
        }

    }

    /**
     * @param string $incrementId
     * @return OrderInterface
     * @throws NoSuchEntityException
     */
    private function getOrderByIncrementId(string $incrementId): OrderInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('increment_id', $incrementId)->create();
        $searchResult = $this->orderRepository->getList($searchCriteria);
        if ($searchResult->getTotalCount() === 0) {
            throw new NoSuchEntityException(__('No Order with increment id %1', $incrementId));
        }

        return current($searchResult->getItems());
    }
}
