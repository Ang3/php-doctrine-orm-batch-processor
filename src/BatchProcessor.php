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
     * @param iterable|QueryBuilder|Query $entities
     */
    public function persist($entities, array $context = []): int
    {
        return $this
            ->createProcess($context)
            ->persist($entities);
    }

    /**
     * @param iterable|QueryBuilder|Query $entities
     */
    public function remove($entities, array $context = []): int
    {
        return $this
            ->createProcess($context)
            ->remove($entities);
    }

    /**
     * @param QueryBuilder|Query $query
     */
    public function iterate($query, array $context = []): Generator
    {
        yield from $this
            ->createProcess($context)
            ->iterate($query);
    }

    public function createProcess(array $context = []): BatchProcess
    {
        return new BatchProcess($this->entityManager, $context);
    }
}
