<?php

namespace Ang3\Component\Doctrine\ORM;

use Doctrine\DBAL\Logging\SQLLogger;
use Doctrine\ORM\EntityManagerInterface;
use Generator;

class BatchProcess
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var SQLLogger|null
     */
    private $sqlLogger;

    /**
     * @var BatchContext
     */
    private $context;

    public function __construct(EntityManagerInterface $entityManager, array $options = [])
    {
        $this->entityManager = $entityManager;
        $entityManager
            ->getConfiguration()
            ->getSQLLogger();
        $this->context = BatchContext::create($options);
    }

    public function iterate(iterable $entities): Generator
    {
        if ($entities instanceof IterableResult) {
            $iterateOnBatchProcess = true;
            $context = $entities->getContext();
            if ($context === $this->context) {
                $context = clone $context;
                $entities->setContext($context);
            }

            $context
                ->disableFlushAuto()
                ->disableClearAuto();
        } else {
            $iterateOnBatchProcess = false;
            $this->disableSqlLogger();
        }

        foreach ($entities as $key => $entity) {
            yield $key => $entity;
            $this->clear($key);
        }

        $this->clear();

        if (!$iterateOnBatchProcess) {
            $this->restoreSqlLogger();
        }
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    public function getContext(): BatchContext
    {
        return $this->context;
    }

    public function setContext(BatchContext $context): self
    {
        $this->context = $context;

        return $this;
    }

    /**
     * @internal
     */
    private function clear(int $key = null): void
    {
        if ($key && (1 === $key || 0 !== ($key % $this->context->getSize()))) {
            return;
        }

        if ($this->context->isFlushAutoEnabled()) {
            $this->entityManager->flush();
        }

        if ($this->context->isClearAutoEnabled()) {
            $this->entityManager->clear();
        }
    }

    /**
     * @internal
     */
    private function disableSqlLogger(): void
    {
        $this->entityManager
            ->getConfiguration()
            ->setSQLLogger(null);
    }

    /**
     * @internal
     */
    private function restoreSqlLogger(): void
    {
        $this->entityManager
            ->getConfiguration()
            ->setSQLLogger($this->sqlLogger);
    }
}
