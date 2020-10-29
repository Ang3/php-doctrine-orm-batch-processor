<?php

namespace Ang3\Component\Doctrine\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use OutOfBoundsException;

class TransactionalEntityBag
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var array<string, array>
     */
    private $entities = [];

    /**
     * @var EntityRepository[]
     */
    private $repositories = [];

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @throws OutOfBoundsException when the entity key was not found
     */
    public function get(string $key): ?object
    {
        $entity = $this->entities[$key] ?? null;

        if (!$entity) {
            throw new OutOfBoundsException(sprintf('Transactional entity with key "%s" was not found', $key));
        }

        return $entity['object'] ?? null;
    }

    public function set(string $key, object $entity): self
    {
        $classMetadata = $this->entityManager->getClassMetadata(get_class($entity));
        $identifier = $classMetadata->getIdentifierValues($entity);

        $this->entities[$key] = [
            'object' => $entity,
            'class' => $classMetadata->getName(),
            'id' => $identifier,
        ];

        return $this;
    }

    public function remove(string $key): void
    {
        if ($this->has($key)) {
            unset($this->entities[$key]);
        }
    }

    public function has(string $key): bool
    {
        return isset($this->entities[$key]);
    }

    public function reload(): void
    {
        foreach ($this->entities as $key => $entity) {
            /** @var class-string $entityClass */
            $entityClass = $entity['class'];

            if (!isset($this->repositories[$entityClass])) {
                /** @var EntityRepository $repository */
                $repository = $this->entityManager->getRepository($entityClass);
                $this->repositories[$entityClass] = $repository;
            }

            $this->entities[$key]['object'] = $this->repositories[$entityClass]->find($entity['id']);
        }
    }
}
