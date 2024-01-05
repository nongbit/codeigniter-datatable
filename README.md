# Codeigniter Datatable
Integrate DataTable to CodeIgniter 4.

## Setup

Download package using composer.

```shell
composer require nongbit/codeigniter-datatable
```

Open your model.

```php
...

use Nongbit\DataTable\Traits\DataTable;

class Foo extends Model
{
    use DataTable;
}
```

## Usage

Now you can write code like this:

```php
$builder->join('comments', 'comments.id = blogs.id', 'left')->datatables();
```

This will return an array like this:

```
Array
(
    [draw] => 1
    [recordsTotal] => 1000
    [recordsFiltered] => 200
    [data] => Array()
)
```
