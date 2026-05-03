<?php

use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductGroup;
use App\Models\ProductModel;
use App\Models\ProductType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function makeProductCatalogForApi(string $suffix): array
{
    $brand = ProductBrand::query()->create(['name' => "Brand {$suffix}"]);
    $model = ProductModel::query()->create([
        'name' => "Model {$suffix}",
        'product_brand_id' => $brand->id,
    ]);
    $type = ProductType::query()->create(['name' => "Type {$suffix}"]);
    $group = ProductGroup::query()->create(['name' => "Group {$suffix}"]);

    return compact('brand', 'model', 'type', 'group');
}

it('requires authentication to create a product via api', function () {
    $catalog = makeProductCatalogForApi((string) str()->uuid());

    $payload = [
        'product_group_id' => $catalog['group']->id,
        'product_type_id' => $catalog['type']->id,
        'product_brand_id' => $catalog['brand']->id,
        'product_model_id' => $catalog['model']->id,
        'code' => 'SKU-001',
        'name' => 'Product API',
        'note' => 'Created by API',
    ];

    $this->postJson('/api/products', $payload)->assertUnauthorized();
});

it('requires authentication to list products via api', function () {
    $this->getJson('/api/products')->assertUnauthorized();
});

it('requires authentication to show a product via api', function () {
    $catalog = makeProductCatalogForApi((string) str()->uuid());
    $product = Product::query()->create([
        'product_group_id' => $catalog['group']->id,
        'product_type_id' => $catalog['type']->id,
        'name' => 'Product Show Auth',
    ]);

    $this->getJson("/api/products/{$product->id}")->assertUnauthorized();
});

it('lists products when user has view permissions', function () {
    Permission::query()->firstOrCreate(['name' => 'view_any_product', 'guard_name' => 'web']);
    Permission::query()->firstOrCreate(['name' => 'view_product', 'guard_name' => 'web']);

    $catalog = makeProductCatalogForApi((string) str()->uuid());
    Product::query()->create([
        'product_group_id' => $catalog['group']->id,
        'product_type_id' => $catalog['type']->id,
        'name' => 'Product List 1',
    ]);
    Product::query()->create([
        'product_group_id' => $catalog['group']->id,
        'product_type_id' => $catalog['type']->id,
        'name' => 'Product List 2',
    ]);

    $user = User::factory()->create();
    $user->givePermissionTo('view_any_product');
    $user->givePermissionTo('view_product');
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/products');

    $response->assertOk()
        ->assertJsonPath('meta.per_page', 25)
        ->assertJsonCount(2, 'data');
});

it('shows a single product when user has view permissions', function () {
    Permission::query()->firstOrCreate(['name' => 'view_any_product', 'guard_name' => 'web']);
    Permission::query()->firstOrCreate(['name' => 'view_product', 'guard_name' => 'web']);

    $catalog = makeProductCatalogForApi((string) str()->uuid());
    $product = Product::query()->create([
        'product_group_id' => $catalog['group']->id,
        'product_type_id' => $catalog['type']->id,
        'name' => 'Product Show',
    ]);

    $user = User::factory()->create();
    $user->givePermissionTo('view_any_product');
    $user->givePermissionTo('view_product');
    Sanctum::actingAs($user);

    $this->getJson("/api/products/{$product->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $product->id)
        ->assertJsonPath('data.name', 'Product Show');
});

it('forbids product reads when user has no view permissions', function () {
    $catalog = makeProductCatalogForApi((string) str()->uuid());
    $product = Product::query()->create([
        'product_group_id' => $catalog['group']->id,
        'product_type_id' => $catalog['type']->id,
        'name' => 'Product No Read Permission',
    ]);

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/products')->assertForbidden();
    $this->getJson("/api/products/{$product->id}")->assertForbidden();
});

it('forbids create when user has no permission', function () {
    $catalog = makeProductCatalogForApi((string) str()->uuid());
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $payload = [
        'product_group_id' => $catalog['group']->id,
        'product_type_id' => $catalog['type']->id,
        'name' => 'Product API',
    ];

    $this->postJson('/api/products', $payload)->assertForbidden();
});

it('creates a product when user has create_product permission', function () {
    Permission::query()->firstOrCreate(['name' => 'create_product', 'guard_name' => 'web']);

    $catalog = makeProductCatalogForApi((string) str()->uuid());
    $user = User::factory()->create();
    $user->givePermissionTo('create_product');
    Sanctum::actingAs($user);

    $payload = [
        'product_group_id' => $catalog['group']->id,
        'product_type_id' => $catalog['type']->id,
        'product_brand_id' => $catalog['brand']->id,
        'product_model_id' => $catalog['model']->id,
        'code' => 'SKU-001',
        'name' => 'Product API',
        'note' => 'Created by API',
    ];

    $response = $this->postJson('/api/products', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Product API')
        ->assertJsonPath('data.code', 'SKU-001');

    $this->assertDatabaseHas('products', [
        'name' => 'Product API',
        'code' => 'SKU-001',
        'product_group_id' => $catalog['group']->id,
        'product_type_id' => $catalog['type']->id,
    ]);
});

it('validates required fields while creating a product', function () {
    Permission::query()->firstOrCreate(['name' => 'create_product', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->givePermissionTo('create_product');
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/products', ['name' => 'Invalid']);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['product_group_id', 'product_type_id']);
});

it('updates a product when user has update_product permission', function () {
    Permission::query()->firstOrCreate(['name' => 'update_product', 'guard_name' => 'web']);

    $catalog = makeProductCatalogForApi((string) str()->uuid());
    $product = Product::query()->create([
        'product_group_id' => $catalog['group']->id,
        'product_type_id' => $catalog['type']->id,
        'product_brand_id' => $catalog['brand']->id,
        'product_model_id' => $catalog['model']->id,
        'name' => 'Before Update',
    ]);

    $user = User::factory()->create();
    $user->givePermissionTo('update_product');
    Sanctum::actingAs($user);

    $response = $this->patchJson("/api/products/{$product->id}", [
        'name' => 'After Update',
        'code' => 'SKU-UPDATED',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'After Update')
        ->assertJsonPath('data.code', 'SKU-UPDATED');

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'After Update',
        'code' => 'SKU-UPDATED',
    ]);
});

it('updates a product with put when user has update_product permission', function () {
    Permission::query()->firstOrCreate(['name' => 'update_product', 'guard_name' => 'web']);

    $catalog = makeProductCatalogForApi((string) str()->uuid());
    $product = Product::query()->create([
        'product_group_id' => $catalog['group']->id,
        'product_type_id' => $catalog['type']->id,
        'product_brand_id' => $catalog['brand']->id,
        'product_model_id' => $catalog['model']->id,
        'name' => 'Before Put Update',
    ]);

    $user = User::factory()->create();
    $user->givePermissionTo('update_product');
    Sanctum::actingAs($user);

    $response = $this->putJson("/api/products/{$product->id}", [
        'name' => 'After Put Update',
        'code' => 'SKU-PUT',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'After Put Update')
        ->assertJsonPath('data.code', 'SKU-PUT');

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'After Put Update',
        'code' => 'SKU-PUT',
    ]);
});

it('forbids update when user has no permission', function () {
    $catalog = makeProductCatalogForApi((string) str()->uuid());
    $product = Product::query()->create([
        'product_group_id' => $catalog['group']->id,
        'product_type_id' => $catalog['type']->id,
        'name' => 'No Update Permission',
    ]);

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->patchJson("/api/products/{$product->id}", [
        'name' => 'Should Fail',
    ])->assertForbidden();
});

it('denies api access when user has no assigned scopes', function () {
    Permission::query()->firstOrCreate(['name' => 'view_any_product', 'guard_name' => 'web']);
    Permission::query()->firstOrCreate(['name' => 'view_product', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->givePermissionTo('view_any_product');
    $user->givePermissionTo('view_product');
    $user->scopes()->detach();
    Sanctum::actingAs($user);

    $this->getJson('/api/products')->assertForbidden();
});
