<?php

namespace Nongbit\DataTable\Traits;

trait DataTable
{
    public function datatable(array $callbacks = [])
    {
        return \Nongbit\DataTable\DataTable::get($this, $callbacks);
    }
}