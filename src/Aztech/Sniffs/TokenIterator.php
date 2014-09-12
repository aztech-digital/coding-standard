<?php

namespace Aztech\Sniffs;

class TokenIterator extends StandardIterator
{
    public function __construct($tokens, $start, $stop)
    {
        parent::__construct(array_slice($tokens, $start, $stop - $start, true));
    }
}
