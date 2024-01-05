<?php

namespace Nongbit\DataTable\Traits;

trait DataTable
{
    public function datatable()
    {
        return \Nongbit\DataTable\DataTable::get($this);
    }
}