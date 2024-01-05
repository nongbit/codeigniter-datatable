<?php

namespace Nongbit\DataTable;

use CodeIgniter\Model;
use Config\Services;

class DataTable
{
    protected static $model;
    protected static $draw, $start, $length, $search, $orders, $columns;

    public static function get(Model $model): array
    {
        self::init($model);

        self::filtering();
        self::ordering();

        $recordsFiltered = count(self::$model->builder()->get(null, 0, false)->getResult());
        $records = self::$model->findAll(self::$length, self::$start);
        $recordsTotal = count($model->findAll());

        return [
            'draw' => self::$draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => array_values($records),
        ];
    }

    public static function init(Model $model): void
    {
        self::$model = $model;
        self::$draw = Services::request()->getGet('draw');
        self::$start = Services::request()->getGet('start');
        self::$length = Services::request()->getGet('length');
        self::$search = Services::request()->getGet('search');
        self::$orders = Services::request()->getGet('order');
        self::$columns = Services::request()->getGet('columns');
    }

    public static function filtering(): ?Model
    {
        if (empty(self::$search['value'])) return self::$model;

        foreach (self::$columns as $column) {
            if ($column['searchable'] === 'true') {
                self::$model->orLike(!empty($column['name']) ? $column['name'] : $column['data'], self::$search['value']);
                if (! empty($column['search']['value'])) {
                    self::$model->like(!empty($column['name']) ? $column['name'] : $column['data'], $column['search']['value']);
                }
            }
        }

        return self::$model;
    }

    public static function ordering(): Model
    {
        foreach (self::$orders as $order) {
            if (self::$columns[$order['column']]['orderable'] === 'true') {
                self::$model->orderBy(!empty(self::$columns[$order['column']]['name']) ? self::$columns[$order['column']]['name'] : self::$columns[$order['column']]['data'], $order['dir']);
            }
        }

        return self::$model;
    }
}