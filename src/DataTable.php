<?php

namespace Nongbit\DataTable;

use CodeIgniter\Model;
use Config\Services;

class DataTable
{
    protected static $model;
    protected static $request;

    public static function get(Model $model): array
    {
        self::$model = $model;
        self::$request = Services::request()->getGet();

        self::filtering();
        self::ordering();

        $filteredRecords = count(self::$model->builder()->get(self::$request['length'], self::$request['start'], false)->getResult());
        $totalRecords = count(self::$model->findAll());
        $records = self::$model->findAll(self::$request['length'], self::$request['start']);

        return [
            'draw' => self::$request['draw'],
            'recordsFiltered' => $filteredRecords,
            'recordsTotal' => $totalRecords,
            'data' => $records,
            'request' => self::$request,
        ];
    }

    protected static function filtering(): void
    {
        if (empty(self::$request['search']['value'])) return;

        foreach (self::$request['columns'] as $column) {
            if ($column['searchable'] !== 'true') continue;

            self::$model->orLike(!empty($column['name']) ? $column['name'] : $column['data'], self::$request['search']['value']);
        }
    }

    protected static function ordering(): void
    {
        if (!isset(self::$request['order'])) return;

        foreach (self::$request['order'] as $order) {
            $column = self::$request['columns'][$order['column']];

            if ($column['orderable'] != 'true') continue;
            self::$model->orderBy(!empty($column['name']) ? $column['name'] : $column['data'], $order['dir']);
        }
    }
}
