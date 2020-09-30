<?php

namespace Ang3\Component\Doctrine\ORM;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

/**
 * @property EntityManagerInterface $_em
 * @property ClassMetadata          $_class
 */
trait BatchRepositoryTrait
{
    /**
     * @var BatchProcessor|null
     */
    protected $batchProcessor;

    public function deleteBy(Criteria $criteria = null): int
    {
        return $this
            ->getBatchProcessor()
            ->deleteBy($this->_class, $criteria);
    }

    public function iterateBy(Criteria $criteria = null): IterableResult
    {
        return $this
            ->getBatchProcessor()
            ->iterateBy($this->_class, $criteria);
    }

    /**
     * @param QueryBuilder|Query $query
     */
    public function iterate($query, array $options = []): IterableResult
    {
        return $this
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
