Doctrine ORM batch processor
============================

[![Build Status](https://travis-ci.org/Ang3/php-doctrine-orm-batch-processor.svg?branch=master)](https://travis-ci.org/Ang3/php-doctrine-orm-batch-processor) 
[![Latest Stable Version](https://poser.pugx.org/ang3/php-doctrine-orm-batch-processor/v/stable)](https://packagist.org/packages/ang3/php-doctrine-orm-batch-processor) 
[![Latest Unstable Version](https://poser.pugx.org/ang3/php-doctrine-orm-batch-processor/v/unstable)](https://packagist.org/packages/ang3/php-doctrine-orm-batch-processor) 
[![Total Downloads](https://poser.pugx.org/ang3/php-doctrine-orm-batch-processor/downloads)](https://packagist.org/packages/ang3/php-doctrine-orm-batch-processor)

This component helps you to process a large entity result or bulk operations without memory problems.

Summary
=======

- [Installation](#installation)
- [Usage](#usage)
    - [Get started](#get-started)
    - [Batch processing](#batch-processing)
        - [Iterate on entities](#iterate-on-entities)
        - [Bulk operations](#bulk-operations)
        - [Batch context](#batch-context)
        - [Transactional entity bag](#transactional-entity-bag)
    - [Repository integration](#repository-integration)
    - [Symfony bundle](#symfony-bundle)

Installation
============

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this component:

```console
$ composer require ang3/php-doctrine-orm-batch-processor
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Usage
=====

Get started
-----------

You just have to create a new instance with an entity manager:

```php
use Ang3\Component\Doctrine\ORM\BatchProcessor;

/** @var \Doctrine\ORM\EntityManagerInterface $entityManager */

$batchProcessor = new BatchProcessor($entityManager);
```

Batch processing
----------------

### Iterate on entities

You can process a large result without memory problems using the following approach:

```php
/** @var \Ang3\Component\Doctrine\ORM\BatchProcessor $batchProcessor */
/** @var \Doctrine\ORM\Query|\Doctrine\ORM\QueryBuilder $query */

/** @var \Generator $iterableResult */
$iterableResult = $batchProcessor->iterate($query, $context = []);

foreach($iterableResult as $entity) {
    // Do you stuff here...
    // Use context to perform a flush automatically (see section "Batch context")
}
```

> Iterating results is not possible with queries that fetch-join a collection-valued association. 
> The nature of such SQL result sets is not suitable for incremental hydration.
> - [doctrine-project.org](https://www.doctrine-project.org/projects/doctrine-orm/en/2.7/reference/batch-processing.html#iterating-results)

### Bulk operations

You can process a bulk insert/update or deletion without memory problems using the following approach:

```php
/** @var \Ang3\Component\Doctrine\ORM\BatchProcessor $batchProcessor */
/** @var iterable $entities */

// Insert/update entities
$batchProcessor->persist($entities, $context = []);

// Delete entities
$batchProcessor->remove($entities, $context = []);
```


### Batch context

Here is a list of context parameters you can pass to the methods of a batch processor:

- ```batch_size``` (int) Number of iterations before clearing the entity manager [default: ```20```].
- ```flush_auto``` (bool) If enabled, the processor will flush the entity manager on X iterations (batch size) 
[default: ```true```].
- ```clear_auto``` (bool) If enabled, the processor will clear the entity manager on X iterations (batch size) 
[default: ```true```].

### Transactional entity bag

*Added in v2.0* - Sometimes, you would want to keep in memory some entities during the process because 
all entities are detached when the processor clears the entity manager. The entity bag trick has been developed 
to register your *transactional entities* in a bag. Each time the process clears the entity manager, all 
entities in the bag are automatically reloaded. Then, you can retrieve your entity with a *key* (the instance changed).

You have to manipulate the process itself, and you can do that by creating a process manually:

```php
/** @var \Ang3\Component\Doctrine\ORM\BatchProcessor $batchProcessor */
/** @var \Ang3\Component\Doctrine\ORM\BatchProcess $process */
$process = $batchProcessor->createProcess($context = []);

// Get the bag from the process
$entityBag = $process->getTransactionalEntityBag();

// Register your entities with a unique key
$entityBag
    ->set('entity_1', $myEntity1)
    ->set('entity_2', $myEntity2)
    // ...
;

// Persist entities
/** @var iterable $entities */
$process->persist($entities);

// Retrieve your entities from the bag
// An \OutOfBoundsException exception is thrown if the key was not found.
$myEntity1 = $entityBag->get('entity_1');
$myEntity2 = $entityBag->get('entity_2');
// ...
```

Repository integration
----------------------

Last but not least, I suggest you to use the trait ```Ang3\Component\Doctrine\ORM\BatchRepositoryTrait``` 
to be able to iterate or remove entities directly from your repositories:

```php
use Ang3\Component\Doctrine\ORM\BatchRepositoryTrait;
use Doctrine\ORM\EntityRepository;

class MyRepository extends EntityRepository
{
    use BatchRepositoryTrait;
    
    // ...
}
```

### Available methods

**Delete entities by criteria**

```php
public function removeBy(\Doctrine\Common\Collections\Criteria $criteria = null, array $context = []): int;
```

**Iterate on entities by criteria**

```php
public function iterateBy(\Doctrine\Common\Collections\Criteria $criteria = null, array $context = []): \Generator;
```

**Iterate on entities from a query or a query builder**

```php
/**
 * @param \Doctrine\ORM\QueryBuilder|\Doctrine\ORM\Query $query
 */
public function iterate($query, array $context = []): \Generator;
```

Symfony bundle
--------------

The bundle [ang3/doctrine-orm-process-bundle](https://github.com/Ang3/doctrine-orm-process-bundle) 
integrates this component and configures a batch processor service for each configured manager.

That's it!