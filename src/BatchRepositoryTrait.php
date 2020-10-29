<?php

namespace Ang3\Component\Doctrine\ORM;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;
use Generator;

/**
 * @property EntityManagerInterface $_em
 * @property ClassMetadata          $_class
 *
 * @method QueryBuilder createQueryBuilder($alias, $indexBy = null)
 */
trait BatchRepositoryTrait
{
    /**
     * @var BatchProcessor|null
     */
    protected $batchProcessor;

    /**
     * @throws QueryException on invalid criteria
     */
    public function removeBy(Criteria $criteria = null, array $options = []): int
    {
        $iterableResult = $this->iterateBy($criteria);

        return $this
            ->getBatchProcessor()
            ->remove($iterableResult, $options);
    }

    /**
     * @throws QueryException on invalid criteria
     */
    public function iterateBy(Criteria $criteria = null, array $options = []): Generator
    {
        $qb = $this
            ->createQueryBuilder('this')
            ->addCriteria($criteria);

        yield from $this->iterate($qb, $options);
    }

    /**
     * @param QueryBuilder|Query $query
     */
    public function iterate($query, array $options = []): Generator
    {
        yield from $this
            ->getBatchProcessor()
            ->iterate($query, $options);
    }

    public function getBatchProcessor()
    {
        if (!$this->batchProcessor) {
            $this->batchProcessor = new BatchProcessor($this->_em);
        }

        return $this->batchProcessor;
    }
}
