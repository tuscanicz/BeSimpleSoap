<?php

namespace BeSimple\SoapCommon\Storage\AbstractStorage;

abstract class AbstractStorage
{
    private $items;

    protected function getItems()
    {
        $items = $this->items;
        $this->resetItems();

        return $items;
    }

    protected function setItems(array $items)
    {
        $this->items = $items;
    }

    private function resetItems()
    {
        $this->items = [];
    }
}
