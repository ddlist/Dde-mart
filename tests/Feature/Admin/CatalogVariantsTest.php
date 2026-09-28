<?php

namespace Tests\Feature\Admin;

use App\Models\CatalogAttribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * DDE-Mart Admin — variant pricing + specs + detail tests (original).
 */
class CatalogVariantsTest extends TestCase
{
    use RefreshDatabase;

    protected function staff(): User
    {
        $role = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'is_super' => true],
        );

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_variants_specs_save_and_show(): void
    {
        $admin = $this->staff();
        $category = Category::create(['name' => 'Pizza', 'slug' => 'pizza']);
        $attribute = CatalogAttribute::create(['name' => 'Size', 'slug' => 'size']);
        $large = $attribute->values()->create(['value' => 'Large']);

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Margherita',
            'category_id' => $category->id,
            'price' => 10,
            'quantity' => 5,
            'attributes' => [$large->id],
            'variants' => [$large->id => ['price' => 2.5, 'quantity' => 3]],
            'specs' => [['label' => 'Weight', 'value' => '500g'], ['label' => '', 'value' => '']],
        ])->assertRedirect(route('admin.products.index'));

        $product = Product::where('name', 'Margherita')->firstOrFail();
        $pivot = $product->attributeValues()->where('attribute_values.id', $large->id)->firstOrFail()->pivot;
        $this->assertEquals(2.5, (float) $pivot->price_delta);
        $this->assertEquals(3, (int) $pivot->quantity);
        $this->assertEquals([['label' => 'Weight', 'value' => '500g']], $product->fresh()->specs);

        $this->actingAs($admin)->get(route('admin.products.show', $product))
            ->assertOk()
            ->assertSee('Large')
            ->assertSee('Weight');
    }
}
