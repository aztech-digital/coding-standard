<?php

namespace Aztech\Sniffs;

class TokenIterator extends StandardIterator
{
    public function __construct($tokens, $start, $stop)
    {
        parent::__construct(array_slice($tokens, $start, $stop - $start, true));
    }

    public function reverse()
    {
        $items = array_reverse($this->items, true);

        return new StandardIterator($items);
    }
}
