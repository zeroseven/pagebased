<?php

declare(strict_types=1);

namespace Zeroseven\Rampage\Pagination;

class Range
{
    protected int $from;
    protected int $to;
    protected int $length;

    public function __construct(Pagination $pagination, Stage $stage)
    {
        $this->from = array_sum(array_slice($pagination->getStageLengths(), 0, $stage->getIndex()));
        $this->length = min($pagination->getStageLengths()[$stage->getIndex()], count($pagination->getItems()) - $this->from);
        $this->to = $this->from + $this->length;
    }

    public function getFrom(): int
    {
        return $this->from;
    }

    public function getTo(): int
    {
        return $this->to;
    }

    public function getLength(): int
    {
        return $this->length;
    }
}
