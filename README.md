# CodeIgniter 4 DataTable

Server-side DataTables integration for CodeIgniter 4.  
**PHP only** – you write your own frontend (plain JS, jQuery, or any tool).

## Requirements

- PHP 8.1+
- CodeIgniter 4.4+
- Composer

## Installation

```bash
composer require nongbit/codeigniter-datatable
```

## Usage

Use `DataTableTrait` (recommended) or instantiate `DataTable` directly.

```php
<?php

namespace App\Controllers;

use App\Models\UserModel;
use Nongbit\DataTable\Traits\DataTableTrait;

class UserController extends BaseController
{
    use DataTableTrait;

    public function datatable()
    {
        $model = new UserModel();
        $builder = $model->builder();
        $dt = $this->getDatatable($builder);
        return $this->response->setJSON($dt->toArray());
    }
}
```

## Complex Queries (Where, Join, Group By, Order By)

You can modify the `BaseBuilder` **before** passing it to `datatable()`. All query conditions will be respected.

### Example with WHERE and JOIN

```php
public function datatable()
{
    $builder = $this->db->table('orders o')
        ->select('o.id, o.customer_id, c.name as customer_name, o.total, o.status')
        ->join('customers c', 'c.id = o.customer_id', 'left')
        ->where('o.status !=', 'cancelled')
        ->where('o.total >', 1000);

    $dt = $this->getDatatable($builder);
    return $this->response->setJSON($dt->toArray());
}
```

### Example with GROUP BY and HAVING

```php
public function datatable()
{
    $builder = $this->db->table('sales')
        ->select('product_id, SUM(amount) as total_sales')
        ->groupBy('product_id')
        ->having('SUM(amount) >', 5000);

    $dt = $this->getDatatable($builder);
    return $this->response->setJSON($dt->toArray());
}
```

### Example with Complex WHERE Conditions

```php
public function datatable()
{
    $builder = $this->db->table('products')
        ->where('price >=', 100)
        ->where('stock >', 0)
        ->groupStart()
            ->where('category', 'electronics')
            ->orWhere('category', 'computers')
        ->groupEnd()
        ->orderBy('created_at', 'DESC');

    $dt = $this->getDatatable($builder);
    return $this->response->setJSON($dt->toArray());
}
```

### Example with Raw SQL Conditions

```php
public function datatable()
{
    $builder = $this->db->table('employees')
        ->select('*, YEAR(hire_date) as hire_year')
        ->where('YEAR(hire_date) >', 2020)
        ->orderBy('hire_year', 'DESC');

    $dt = $this->getDatatable($builder);
    return $this->response->setJSON($dt->toArray());
}
```

> **Important:** The package automatically adds `LIMIT` and `OFFSET` for pagination, and applies `LIKE` conditions for searching. It does **not** remove any existing `WHERE`, `GROUP BY`, `HAVING`, or `ORDER BY` clauses. However, if you specify an `ORDER BY` and DataTables sends its own ordering, both will be applied (your default ordering first, then DataTables ordering). To avoid conflicts, consider removing `orderBy()` from the builder if you want DataTables to fully control ordering.

## Without Trait (Direct Instantiation)

```php
use Nongbit\DataTable\DataTable;

$builder = $model->builder()->where('active', 1);
$dt = new DataTable($builder);
return $this->response->setJSON($dt->toArray());
```

## Column Formatting with Callbacks

Use `setCallback()` to transform column data before sending to the frontend.

```php
$dt->setCallback('created_at', function($value, $row) {
    return date('d-m-Y', strtotime($value));
});
```

The callback receives `$value` (raw column value) and `$row` (full record array).

## Important Notes

- The package automatically handles searching, ordering, and pagination based on DataTables request parameters.
- DataTables individual column search (`columns[i][search][value]`) is supported.
- For complex queries with table aliases or joins, set the `name` attribute in your DataTables column definition to avoid ambiguous column names (e.g., `{ data: 'customer_name', name: 'c.name' }`).
- The builder is cloned internally to prevent side effects.
- If you use parameterized `where()` clauses, bindings are preserved in the clone – this is usually safe.

## Troubleshooting

### Ambiguous column name in ORDER BY

If you get an error like `Column 'id' in order clause is ambiguous`, set the `name` attribute in your DataTables column definition:

```javascript
columns: [
    { data: 'order_id', name: 'o.id' },
    { data: 'customer_name', name: 'c.name' }
]
```

### Custom search logic

The package uses `LIKE` for searching. If you need different logic (e.g., full-text search), extend the class or modify the builder before passing.

## Bonus: Integration with Vite

If you use `nongbit/codeigniter-vite`, you can write your own `datatable.js` and import it into your Vite entry point. Example:

```javascript
// app/Views/assets/js/datatable.js
import DataTable from 'datatables.net-dt';
import 'jquery';

export function initDataTable(selector, ajaxUrl) {
	return new DataTable(selector, {
		processing: true,
		serverSide: true,
		ajax: ajaxUrl,
		columns: [
			{ data: 'id', name: 'id' },
			{ data: 'name', name: 'name' },
			{ data: 'email', name: 'email' },
		]
	});
}

// app/Views/assets/js/app.js
import { initDataTable } from './datatable.js';
initDataTable('#usersTable', '/user/datatable');
```

Then in your view: `<?= vite('app') ?>`.

This package provides no frontend assets; you are free to use CDN, Vite, or any other method.

## License

MIT
