<?php

namespace Nongbit\DataTable;

use CodeIgniter\Model;
use Config\Services;

class DataTable
{
    protected static $model;
    protected static $draw, $start, $length, $search, $orders, $columns;
    protected static $callbacks;

    public static function get(Model $model, array $callbacks): array
    {
        self::init($model, $callbacks);

        self::filtering();
        self::ordering();

        $recordsFiltered = count(self::$model->builder()->get(null, 0, false)->getResult());
        $records = self::$model->findAll(self::$length, self::$start);
        $recordsTotal = count($model->findAll());

        self::callbacks($records);

        return [
            'draw' => self::$draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => array_values($records),
        ];
    }

    protected static function init(Model $model, array $callbacks): void
    {
        self::$model = $model;
        self::$draw = Services::request()->getGet('draw');
        self::$start = Services::request()->getGet('start');
        self::$length = Services::request()->getGet('length');
        self::$search = Services::request()->getGet('search');
        self::$orders = Services::request()->getGet('order');
        self::$columns = Services::request()->getGet('columns');

        self::$callbacks = $callbacks;
    }

    protected static function filtering(): ?Model
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

    protected static function ordering(): Model
    {
        if (empty(self::$orders)) return self::$model;

        foreach (self::$orders as $order) {
            if (self::$columns[$order['column']]['orderable'] === 'true') {
                self::$model->orderBy(!empty(self::$columns[$order['column']]['name']) ? self::$columns[$order['column']]['name'] : self::$columns[$order['column']]['data'], $order['dir']);
            }
        }

        return self::$model;
    }

    protected static function callbacks(array $records)
    {
        foreach (self::$callbacks as $field => $callback) {
            foreach ($records as $record) {
                $data = (object) $record;
                $record->$field = $callback($record->$field, $data);
            }
        }
    }
}