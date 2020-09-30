<?php

namespace Ang3\Component\Doctrine\ORM;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Generator;

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

    public function removeBy(string $class, Criteria $criteria = null, array $options = []): int
    {
        $iterableResult = $this->iterateBy($class, $criteria);

        return $this->remove($iterableResult, $options);
    }

    public function iterateBy(string $class, Criteria $criteria = null, array $options = []): IterableResult
    {
        $qb = $this->entityManager
            ->createQueryBuilder()
            ->select('this')
            ->from($class, 'this');

        if ($criteria) {
            try {
                $qb->addCriteria($criteria);
            } catch (Query\QueryException $e) {
                throw new \LogicException(sprintf('Failed to add criteria for batch iterations - %s', $e->getMessage()));
            }
        }

        return $this->iterate($qb, $options);
    }

    /**
     * @param QueryBuilder|Query $query
     */
    public function iterate($query, array $options = []): IterableResult
    {
        $query = $query instanceof Query ? $query : $query->getQuery();

        return new IterableResult($query, $options);
    }

    /**
     * @param object[] $entities
     */
    public function persist(iterable $entities, array $options = []): int
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
     * @param object[] $entities
     */
    public function remove(iterable $entities, array $options = []): int
    {
        $entities = $this->iterateEntities($entities, $options);
        $count = 0;

        foreach ($entities as $entity) {
            $this->entityManager->remove($entity);
            ++$count;
        }

        return $count;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * @internal
     */
    private function iterateEntities(iterable $entities, array $options = []): Generator
    {
        $batchProcess = new BatchProcess($this->entityManager, array_merge($options, [
            'flush_auto' => true,
        ]));

        yield from $batchProcess
            ->iterate($entities);
    }
}
