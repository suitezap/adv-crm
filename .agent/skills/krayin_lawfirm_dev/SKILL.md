---
name: krayin_lawfirm_dev
description: Development standards, directory structure, and best practices for the SuiteZap/LawFirm package in Krayin CRM.
---

# Krayin & LawFirm Development Standards

This skill outlines the standard procedures for developing within the **SuiteZap/LawFirm** package on **Krayin CRM**. Follow these patterns to ensure consistency and avoid rework.

## 1. Directory Structure

All LawFirm code resides in `packages/SuiteZap/LawFirm/src/`.

- **Config/**: Configuration files (`menu.php`, `acl.php`, `system.php`).
- **DataGrids/**: DataGrid definitions (`Webkul\DataGrid\DataGrid`).
- **Http/Controllers/Admin/**: Admin controllers (`ProcessoController.php`).
- **Models/**: Eloquent models.
- **Resources/views/admin/**: Blade templates.
- **Routes/**: Route definitions (`admin.php`).
- **Repositories/**: Repository classes.

## 2. Naming Conventions

- **Controllers**: `[Entity]Controller.php` (e.g., `ProcessoController.php`).
- **DataGrids**: `[Entity]DataGrid.php` (e.g., `ProcessoDataGrid.php`).
- **Models**: `[Entity].php` (e.g., `Processo.php`).
- **Repositories**: `[Entity]Repository.php`.
- **Routes**: `admin.[module].[action]` (e.g., `admin.processos.index`).
- **Views**: `lawfirm::admin.[module].[view]` (e.g., `lawfirm::admin.processos.index`).
- **Translations**: `lawfirm::app.[module].[key]`.

## 3. Creating a New Module (CRUD)

### Step 3.1: Define Routes (`Routes/admin.php`)

Use the `Route::controller` group pattern inside `admin/juridico` prefix.

```php
Route::controller([Entity]Controller::class)->prefix('[entities]')->group(function () {
    Route::get('', 'index')->name('admin.[entities].index');
    Route::get('create', 'create')->name('admin.[entities].create');
    Route::post('create', 'store')->name('admin.[entities].store');
    Route::get('{id}', 'show')->name('admin.[entities].show');
    Route::get('{id}/edit', 'edit')->name('admin.[entities].edit');
    Route::put('{id}', 'update')->name('admin.[entities].update');
    Route::delete('{id}', 'destroy')->name('admin.[entities].destroy');
});
```

### Step 3.2: Create Controller (`Http/Controllers/Admin/[Entity]Controller.php`)

Must extend `Webkul\Admin\Http\Controllers\Controller`. 
**Important**: Use Repositories for DB interaction, not Models directly if possible.

```php
namespace SuiteZap\LawFirm\Http\Controllers\Admin;

use Webkul\Admin\Http\Controllers\Controller;
use SuiteZap\LawFirm\DataGrids\[Entity]DataGrid;
use SuiteZap\LawFirm\Repositories\[Entity]Repository;
use Illuminate\Support\Facades\Event;

class [Entity]Controller extends Controller
{
    protected $[entity]Repository;

    public function __construct([Entity]Repository $[entity]Repository)
    {
        $this->[entity]Repository = $[entity]Repository;
    }

    public function index()
    {
        if (request()->ajax()) {
            return app([Entity]DataGrid::class)->process();
        }
        return view('lawfirm::admin.[entities].index');
    }

    public function store()
    {
        $this->validate(request(), [
            // Validation rules
        ]);

        Event::dispatch('lawfirm.[entity].create.before');
        
        $entity = $this->[entity]Repository->create(request()->all());
        
        Event::dispatch('lawfirm.[entity].create.after', $entity);

        session()->flash('success', trans('lawfirm::app.[entities].create-success'));
        return redirect()->route('admin.[entities].index');
    }
    
    public function update($id)
    {
         $this->validate(request(), [
            // Validation rules
        ]);
        
        Event::dispatch('lawfirm.[entity].update.before', $id);
        
        $entity = $this->[entity]Repository->update(request()->all(), $id);
        
        Event::dispatch('lawfirm.[entity].update.after', $entity);
        
        session()->flash('success', trans('lawfirm::app.[entities].update-success'));
        return redirect()->route('admin.[entities].index');
    }
    
    public function destroy($id)
    {
        try {
            Event::dispatch('lawfirm.[entity].delete.before', $id);
            
            $this->[entity]Repository->delete($id);
            
            Event::dispatch('lawfirm.[entity].delete.after', $id);

            return response()->json([
                'message' => trans('lawfirm::app.[entities].delete-success'),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => trans('lawfirm::app.[entities].delete-failed'),
            ], 500);
        }
    }
}
```

### Step 3.3: Create DataGrid (`DataGrids/[Entity]DataGrid.php`)

Must extend `Webkul\DataGrid\DataGrid`.

```php
namespace SuiteZap\LawFirm\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class [Entity]DataGrid extends DataGrid
{
    protected $primaryColumn = 'id';
    protected $sortOrder = 'desc';

    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('[table_name]')
            ->addSelect('id', 'name', 'created_at');

        // Security / ACL Logic
        $user = auth()->guard('user')->user();
        if ($user && $user->role_id != 1) {
            // $queryBuilder->where('user_id', $user->id); // Example scoping
        }

        $this->setQueryBuilder($queryBuilder);
    }

    public function prepareColumns()
    {
        $this->addColumn([
            'index' => 'id',
            'label' => trans('lawfirm::app.common.id'),
            'type' => 'integer',
            'sortable' => true,
            'filterable' => true,
        ]);
        
        // Other columns...
    }

    public function prepareActions()
    {
        $this->addAction([
            'icon' => 'icon-eye',
            'title' => trans('lawfirm::app.common.view'),
            'method' => 'GET',
            'url' => function ($row) {
                return route('admin.[entities].show', $row->id);
            },
        ]);
        
        $this->addAction([
            'icon' => 'icon-edit',
            'title' => trans('lawfirm::app.common.edit'),
            'method' => 'GET',
            'url' => function ($row) {
                return route('admin.[entities].edit', $row->id);
            },
        ]);
        
        $this->addAction([
            'icon' => 'icon-delete',
            'title' => trans('lawfirm::app.common.delete'),
            'method' => 'DELETE',
            'url' => function ($row) {
                return route('admin.[entities].destroy', $row->id);
            },
        ]);
    }
}
```

### Step 3.4: Register in Config

#### `Config/menu.php`
```php
[
    'key' => 'lawfirm.[entities]',
    'name' => 'My Entity',
    'route' => 'admin.[entities].index',
    'sort' => 10,
    'icon-class' => 'icon-setting', 
    'permission' => 'lawfirm.[entities].view',
],
```

#### `Config/acl.php`
(If applicable for defining granular permissions)

## 4. Common Pitfalls to Avoid

- **Direct Model Access in Controller**: Prefer Repositories.
- **Middleware**: Do not manually call `$this->middleware('acl:...')` in `__construct`. Permission checking is typically handled by the `user` middleware and `acl.php`.
- **DataGrid JSON**: `index()` method MUST check `request()->ajax()` and return `app(Grid::class)->process()` or the view accordingly.
- **Translations**: Always use `trans('lawfirm::...')` for UI text.
- **Route Parameters**: Ensure route parameters match what the controller expects (usually `id`).
