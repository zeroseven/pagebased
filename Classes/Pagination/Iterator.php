<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Pagination;

class Iterator
{
    private int $total;
    private int $index;

    public function __construct(int $total, int $index = null)
    {
        $this->total = $total;
        $this->index = $index ?? 0;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function getCycle(): int
    {
        return $this->index + 1;
    }

    public function isFirst(): bool
    {
        return $this->index === 0;
    }

    public function getIsFirst(): bool
    {
        return $this->isFirst();
    }

    public function isLast(): bool
    {
        return $this->getCycle() === $this->total;
    }

    public function getIsLast(): bool
    {
        return $this->isLast();
    }

    public function isEven(): bool
    {
        return $this->getCycle() % 2 === 0;
    }

    public function getIsEven(): bool
    {
        return $this->isEven();
    }

    public function isOdd(): bool
    {
        return !$this->isEven();
    }

    public function getIsOdd(): bool
    {
        return $this->isOdd();
    }

    public function count(): void
    {
        $this->index++;
    }
}
