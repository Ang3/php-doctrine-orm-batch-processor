<?php

namespace Ang3\Component\Doctrine\ORM;

use Doctrine\ORM\Query;
use Generator;

class IterableResult extends BatchProcess implements \IteratorAggregate
{
    /**
     * @var Query
     */
    private $query;

    public function __construct(Query $query, array $options = [])
    {
        parent::__construct($query->getEntityManager(), $options);
        $this->query = $query;
    }

    /**
     * @return Generator|object[]
     */
    public function getIterator(): Generator
    {
        $iterator = parent::iterate($this->query->iterate());

        foreach ($iterator as $value) {
            yield $value[0];
        }
    }

    public function getQuery(): Query
    {
        return $this->query;
    }
}
