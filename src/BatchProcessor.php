<?php

namespace Ang3\Component\Doctrine\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Generator;
use InvalidArgumentException;

class BatchProcessor
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param iterable|QueryBuilder|Query $entities
     */
    public function persist($entities, array $options = []): int
    {
        $entities = $this->iterateEntities($entities, $options);
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
    public function remove($entities, array $options = []): int
    {
        $entities = $this->iterateEntities($entities, $options);
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
    public function iterate(object $query, array $options = []): IterableResult
    {
        if (!($query instanceof Query || $query instanceof QueryBuilder)) {
            throw $this->createInvalidQueryArgumentException('query', $query);
        }

        $query = $query instanceof Query ? $query : $query->getQuery();

        return new IterableResult($query, $options);
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * @internal
     *
     * @param mixed $entities
     *
     * @throws InvalidArgumentException when the argument $entities is not valid
     */
    private function iterateEntities($entities, array $options = []): Generator
    {
        if (!is_iterable($entities)) {
            if (!($entities instanceof Query) && !($entities instanceof QueryBuilder)) {
                throw $this->createInvalidQueryArgumentException('entities', $entities, 'iterable');
            }

            $entities = $this->iterate($entities, $options);
        }

        $batchProcess = new BatchProcess($this->entityManager, array_merge($options, [
            'flush_auto' => true,
        ]));

        yield from $batchProcess->iterate($entities);
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
}
