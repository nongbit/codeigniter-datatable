<?php

namespace Nongbit\DataTable;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\HTTP\RequestInterface;

class DataTable
{
    private BaseBuilder $builder;
    private array $request;
    private array $columns;
    private array $callbacks = [];

    public function __construct(BaseBuilder $builder, ?RequestInterface $request = null)
    {
        $this->builder = $builder;
        $this->request = $this->parseRequest($request ?? service('request'));
    }

    private function parseRequest(RequestInterface $request): array
    {
        $rawColumns = $request->getGet('columns') ?? [];
        $this->columns = array_map(function ($col) {
            return [
                'data' => $col['data'] ?? null,
                'name' => $col['name'] ?? null,
                'searchable' => ($col['searchable'] ?? 'false') === 'true',
                'orderable' => ($col['orderable'] ?? 'false') === 'true',
                'search' => [
                    'value' => $col['search']['value'] ?? '',
                    'regex' => ($col['search']['regex'] ?? 'false') === 'true',
                ],
            ];
        }, $rawColumns);

        return [
            'draw' => (int) $request->getGet('draw'),
            'start' => (int) $request->getGet('start'),
            'length' => (int) $request->getGet('length'),
            'search' => [
                'value' => $request->getGet('search')['value'] ?? '',
                'regex' => ($request->getGet('search')['regex'] ?? 'false') === 'true',
            ],
            'order' => array_map(function ($order) {
                return [
                    'column' => (int) $order['column'],
                    'dir' => $order['dir'] === 'desc' ? 'desc' : 'asc',
                ];
            }, $request->getGet('order') ?? []),
            'columns' => $this->columns,
        ];
    }

    public function setCallback(string $column, callable $callback): self
    {
        $this->callbacks[$column] = $callback;
        return $this;
    }

    public function toArray(): array
    {
        $totalRecords = $this->getTotalRecords();
        $filteredRecords = $this->getFilteredRecords();
        $data = $this->fetchData();

        $this->applyCallbacks($data);

        return [
            'draw' => $this->request['draw'],
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    private function getTotalRecords(): int
    {
        return $this->builder->countAllResults(false);
    }

    private function getFilteredRecords(): int
    {
        $clone = clone $this->builder;
        $this->applySearch($clone);
        return $clone->countAllResults(false);
    }

    private function fetchData(): array
    {
        $builder = clone $this->builder;

        $this->applySearch($builder);
        $this->applyColumnSearch($builder);
        $this->applyOrdering($builder);
        $this->applyPagination($builder);

        return $builder->get()->getResultArray();
    }

    private function applySearch(BaseBuilder $builder): void
    {
        $searchValue = trim($this->request['search']['value']);
        if ($searchValue === '') {
            return;
        }

        $builder->groupStart();
        foreach ($this->columns as $col) {
            if (!$col['searchable']) {
                continue;
            }
            $field = $col['name'] ?: $col['data'];
            if ($field) {
                $builder->orLike($field, $searchValue);
            }
        }
        $builder->groupEnd();
    }

    private function applyColumnSearch(BaseBuilder $builder): void
    {
        foreach ($this->columns as $col) {
            $searchValue = trim($col['search']['value'] ?? '');
            if ($searchValue === '' || !$col['searchable']) {
                continue;
            }
            $field = $col['name'] ?: $col['data'];
            if ($field) {
                $builder->like($field, $searchValue);
            }
        }
    }

    private function applyOrdering(BaseBuilder $builder): void
    {
        foreach ($this->request['order'] as $order) {
            $colIdx = $order['column'];
            if (!isset($this->columns[$colIdx]) || !$this->columns[$colIdx]['orderable']) {
                continue;
            }
            $field = $this->columns[$colIdx]['name'] ?: $this->columns[$colIdx]['data'];
            if ($field) {
                $builder->orderBy($field, $order['dir']);
            }
        }
    }

    private function applyPagination(BaseBuilder $builder): void
    {
        $length = $this->request['length'];
        if ($length > 0) {
            $builder->limit($length, $this->request['start']);
        }
    }

    private function applyCallbacks(array &$data): void
    {
        if (empty($this->callbacks)) {
            return;
        }

        foreach ($data as &$row) {
            foreach ($this->callbacks as $field => $callback) {
                if (array_key_exists($field, $row)) {
                    $row[$field] = $callback($row[$field], $row);
                }
            }
        }
    }
}