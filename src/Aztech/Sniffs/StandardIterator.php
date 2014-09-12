<?php

namespace Aztech\Sniffs;

class StandardIterator implements \Iterator
{

    protected $items = array();

    public function __construct(array $items = array())
    {
        $this->items = $items;
    }

    public function rewind()
    {
        return reset($this->items);
    }

    public function next()
    {
        return next($this->items);
    }

    public function current()
    {
        return $this->items[$this->key()];
    }

    public function valid()
    {
        return isset($this->items[$this->key()]);
    }

    public function key()
    {
        return key($this->items);
    }
}
