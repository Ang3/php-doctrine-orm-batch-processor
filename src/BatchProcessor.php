<?php

namespace Ang3\Component\Doctrine\ORM;

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
    public function persist(iterable $entities, array $options = []): void
    {
        $entities = $this->iterateEntities($entities, $options);

        foreach ($entities as $entity) {
            $this->entityManager->persist($entity);
        }
    }

    /**
     * @param object[] $entities
     */
    public function remove(iterable $entities, array $options = []): void
    {
        $entities = $this->iterateEntities($entities, $options);

        foreach ($entities as $entity) {
            $this->entityManager->remove($entity);
        }
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

        yield from $batchProcess->iterate($entities);
    }
}