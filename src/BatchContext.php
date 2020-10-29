<?php

namespace Ang3\Component\Doctrine\ORM;

class BatchContext
{
    public const DEFAULT_SIZE = 20;

    /**
     * @var int
     */
    private $size = self::DEFAULT_SIZE;

    /**
     * @var bool
     */
    private $flushAuto = true;

    /**
     * @var bool
     */
    private $clearAuto = true;

    public static function create(array $options = []): self
    {
        $instance = new self();
        $instance->setSize((int) ($options['batch_size'] ?? self::DEFAULT_SIZE));
        $instance->setFlushAuto((bool) ($options['flush_auto'] ?? true));
        $instance->setClearAuto((bool) ($options['clear_auto'] ?? true));

        return $instance;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function isFlushAutoEnabled(): bool
    {
        return $this->flushAuto;
    }

    public function setFlushAuto(bool $flushAuto): self
    {
        $this->flushAuto = $flushAuto;

        return $this;
    }

    public function isClearAutoEnabled(): bool
    {
        return $this->clearAuto;
    }

    public function setClearAuto(bool $clearAuto): self
    {
        $this->clearAuto = $clearAuto;

        return $this;
    }
}
