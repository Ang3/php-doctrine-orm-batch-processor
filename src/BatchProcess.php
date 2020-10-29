<?php

namespace Ang3\Component\Doctrine\ORM;

use Doctrine\DBAL\Logging\SQLLogger;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Generator;
use InvalidArgumentException;

class BatchProcess
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TransactionalEntityBag
     */
    private $transactionalEntityBag;

    /**
     * @var SQLLogger|null
     */
    private $sqlLogger;

    /**
     * @var BatchContext
     */
    private $context;

    public function __construct(EntityManagerInterface $entityManager, array $context = [])
    {
        $this->entityManager = $entityManager;
        $this->transactionalEntityBag = new TransactionalEntityBag($entityManager);
        $entityManager
            ->getConfiguration()
            ->getSQLLogger();
        $this->context = BatchContext::create($context);
    }

    /**
     * @param iterable|QueryBuilder|Query $entities
     */
    public function persist($entities): int
    {
        $entities = $this->getIterator($entities);
        $count = 0;

        foreach ($entities as $entity) {
            $this->entityManager->persist($entity);
            ++$count;
        }

        return $count;
    }

    /**
     * @param iterable|QueryBuilder|Query $entities
     */
    public function remove($entities): int
    {
        $entities = $this->getIterator($entities);
        $count = 0;

        foreach ($entities as $entity) {
            $this->entityManager->remove($entity);
            ++$count;
        }

        return $count;
    }

    /**
     * @throws InvalidArgumentException when the query object is not valid
     */
    public function iterate(object $query): Generator
    {
        if (!($query instanceof Query || $query instanceof QueryBuilder)) {
            throw $this->createInvalidQueryArgumentException('query', $query);
        }

        $query = $query instanceof Query ? $query : $query->getQuery();
        $iterator = $this->process($query->iterate());

        foreach ($iterator as $value) {
            yield $value[0];
        }
    }

    public function process(iterable $entities): Generator
    {
        $this->disableSqlLogger();

        foreach ($entities as $key => $entity) {
            yield $key => $entity;
            $this->clear($key);
        }

        $this->clear();
        $this->restoreSqlLogger();
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    public function getTransactionalEntityBag(): TransactionalEntityBag
    {
        return $this->transactionalEntityBag;
    }

    public function getContext(): BatchContext
    {
        return $this->context;
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
            $this->transactionalEntityBag->reload();
        }
    }

    /**
     * @internal
     *
     * @param mixed $entities
     *
     * @throws InvalidArgumentException when the argument $entities is not valid
     */
    private function getIterator($entities): Generator
    {
        if (!is_iterable($entities)) {
            if (!($entities instanceof Query) && !($entities instanceof QueryBuilder)) {
                throw $this->createInvalidQueryArgumentException('entities', $entities, 'iterable');
            }

            $entities = $this->iterate($entities);
        }

        yield from $this->process($entities);
    }

    /**
     * @internal
     *
     * @param mixed                $actualValue
     * @param string[]|string|null $allowedTypes
     */
    private function createInvalidQueryArgumentException(string $argumentName, $actualValue, $allowedTypes = null): InvalidArgumentException
    {
        $allowedTypes = is_array($allowedTypes) ? $allowedTypes : (array) $allowedTypes;
        $expectedType = implode('|', array_unique(array_merge($allowedTypes, [Query::class, QueryBuilder::class])));
        $actualType = is_object($actualValue) ? sprintf('instance of "%s"', get_class($actualValue)) : gettype($actualValue);

        throw new InvalidArgumentException(sprintf('The argument $%s must be a value of type "%s", got %s.', $argumentName, $expectedType, $actualType));
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
