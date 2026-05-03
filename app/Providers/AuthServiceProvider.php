<?php

namespace App\Providers;

use App\Models\ReorderOrder;
use App\Models\InventoryLocation;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\Movement;
use App\Models\MovementType;
use App\Models\ProductBrand;
use App\Models\Reorder;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\User;
use App\Models\Scope;
use App\Policies\InventoryLocationPolicy;
use App\Policies\ProductPolicy;
use App\Policies\InventoryPolicy;
use App\Policies\MovementPolicy;
use App\Policies\MovementTypePolicy;
use App\Policies\ProductBrandPolicy;
use App\Policies\ReorderPolicy;
use App\Policies\TaskPolicy;
use App\Policies\TaskStatusPolicy;
use App\Policies\TaskTypePolicy;
use App\Policies\UserPolicy;
use App\Policies\ReorderOrderPolicy;
use App\Policies\ScopePolicy;
// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        ReorderOrder::class => ReorderOrderPolicy::class,
        Product::class => ProductPolicy::class,
        Inventory::class => InventoryPolicy::class,
        Movement::class => MovementPolicy::class,
        InventoryLocation::class => InventoryLocationPolicy::class,
        MovementType::class => MovementTypePolicy::class,
        ProductBrand::class => ProductBrandPolicy::class,
        Reorder::class => ReorderPolicy::class,
        Task::class => TaskPolicy::class,
        TaskStatus::class => TaskStatusPolicy::class,
        TaskType::class => TaskTypePolicy::class,
        User::class => UserPolicy::class,
        Scope::class => ScopePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
