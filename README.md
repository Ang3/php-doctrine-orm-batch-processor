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
 
There are two ways to get an instance of batch processor in your code.

### Dependency injection

In Symfony applications, just use the dependency injection:

```php
use Ang3\Component\Doctrine\ORM\BatchProcessor;

class MyService
{
    private $batchProcessor;

    public function __construct(BatchProcessor $batchProcessor)
    {
        $this->batchProcessor = $batchProcessor;
    }
}
```

### Use as standalone

You can also use the processor as standalone by creating a new instance with an entity manager:

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

$iterableResult = $batchProcessor->iterate($query, $options = []);
/** @var \Ang3\Component\Doctrine\ORM\IterableResult $iterableResult */

foreach($iterableResult as $entity) {
    // Do you stuff here...
    // Use options to perform a flush automatically (see section "Batch context")
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
$batchProcessor->persist($entities, $options = []);

// Delete entities
$batchProcessor->remove($entities, $options = []);
```

**Good to know**

- The argument ```$entities``` could be an instance of ```Ang3\Component\Doctrine\ORM\IterableResult```.
- Internally, the option ```flush_auto``` is enabled automatically.


### Batch context

Here is a list of all options you can pass to the methods of a batch processor:

- ```batch_size``` (int) Number of iterations before clearing the entity manager [default: ```20```].
- ```flush_auto``` (bool) If enabled, the processor will flush the entity manager on X iterations (batch size) 
[default: ```false```].
- ```clear_auto``` (bool) If enabled, the processor will clear the entity manager on X iterations (batch size) 
[default: ```true```].


That's it!