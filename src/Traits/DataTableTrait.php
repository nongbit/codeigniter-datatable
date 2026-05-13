<?php

namespace Nongbit\DataTable\Traits;

use CodeIgniter\Database\BaseBuilder;
use Nongbit\DataTable\DataTable;

trait DataTableTrait
{
    protected function getDatatable(BaseBuilder $builder): DataTable
    {
        return new DataTable($builder, service('request'));
    }
}