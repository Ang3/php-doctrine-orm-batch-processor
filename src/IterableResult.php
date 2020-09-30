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
        yield from parent::iterate($this->query->iterate());
    }

    public function getQuery(): Query
    {
        return $this->query;
    }
}
