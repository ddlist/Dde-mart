<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\CatalogAttribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/*
 * DDE-Mart Admin — catalog CRUD tests (D6, original).
 */
class CatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function superAdmin(): User
    {
        $role = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'is_super' => true],
        );

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_guest_is_redirected_from_catalog(): void
    {
        $this->get(route('admin.products.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_catalog_grant_gets_forbidden(): void
    {
        $role = Role::create(['name' => 'Limited', 'slug' => 'limited']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get(route('admin.sections.index'))->assertForbidden();
    }

    public function test_section_crud_with_auto_slug(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('admin.sections.store'), [
            'name' => 'Fresh Grocery',
            'service_type' => 'grocery',
            'color' => '#ff0000',
        ])->assertRedirect(route('admin.sections.index'));

        $section = Section::where('name', 'Fresh Grocery')->firstOrFail();
        $this->assertEquals('fresh-grocery', $section->slug);

        $this->actingAs($admin)->put(route('admin.sections.update', $section), [
            'name' => 'Fresh Grocery Plus',
        ])->assertRedirect(route('admin.sections.index'));

        $this->assertEquals('fresh-grocery-plus', $section->fresh()->slug);
    }

    public function test_section_delete_blocked_by_category(): void
    {
        $admin = $this->superAdmin();
        $section = Section::create(['name' => 'Food', 'slug' => 'food']);
        Category::create(['name' => 'Pizza', 'slug' => 'pizza', 'section_id' => $section->id]);

        $response = $this->actingAs($admin)->delete(route('admin.sections.destroy', $section));

        $response->assertSessionHas('error');
        $this->assertNotNull($section->fresh());
    }

    public function test_category_create_with_image_upload(): void
    {
        Storage::fake('public');
        $admin = $this->superAdmin();
        $section = Section::create(['name' => 'Food', 'slug' => 'food']);

        $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Burgers',
            'section_id' => $section->id,
            'image' => UploadedFile::fake()->image('burger.jpg'),
            'show_in_homepage' => '1',
        ])->assertRedirect(route('admin.categories.index'));

        $category = Category::where('name', 'Burgers')->firstOrFail();
        $this->assertNotNull($category->image_path);
        Storage::disk('public')->assertExists($category->image_path);
        $this->assertTrue($category->show_in_homepage);
    }

    public function test_brand_crud_and_product_block(): void
    {
        $admin = $this->superAdmin();
        $brand = Brand::create(['name' => 'Acme', 'slug' => 'acme']);

        Product::create(['name' => 'Acme Soda', 'slug' => 'acme-soda', 'price' => 2.50, 'brand_id' => $brand->id]);

        $response = $this->actingAs($admin)->delete(route('admin.brands.destroy', $brand));

        $response->assertSessionHas('error');
        $this->assertNotNull($brand->fresh());
    }

    public function test_attribute_values_sync_inline(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('admin.attributes.store'), [
            'name' => 'Size',
            'values' => [['value' => 'Small'], ['value' => 'Large']],
        ])->assertRedirect(route('admin.attributes.index'));

        $attribute = CatalogAttribute::where('name', 'Size')->firstOrFail();
        $this->assertCount(2, $attribute->values);

        // Update: rename one, drop one, add one.
        $small = $attribute->values()->where('value', 'Small')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.attributes.update', $attribute), [
            'name' => 'Size',
            'values' => [
                ['id' => $small->id, 'value' => 'Medium'],
                ['value' => 'XL'],
            ],
        ])->assertRedirect(route('admin.attributes.index'));

        $this->assertEqualsCanonicalizing(
            ['Medium', 'XL'],
            $attribute->fresh()->values()->pluck('value')->all()
        );
    }

    public function test_product_create_with_addons_and_attributes(): void
    {
        $admin = $this->superAdmin();
        $category = Category::create(['name' => 'Pizza', 'slug' => 'pizza']);
        $attribute = CatalogAttribute::create(['name' => 'Spice', 'slug' => 'spice']);
        $mild = $attribute->values()->create(['value' => 'Mild']);

        $response = $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Margherita',
            'category_id' => $category->id,
            'price' => 9.99,
            'discount_price' => 7.99,
            'quantity' => 50,
            'attributes' => [$mild->id],
            'addons' => [
                ['name' => 'Extra cheese', 'price' => 1.50],
                ['name' => '', 'price' => ''], // blank row ignored
            ],
        ]);

        $response->assertRedirect(route('admin.products.index'));

        $product = Product::where('name', 'Margherita')->firstOrFail();
        $this->assertEquals(7.99, $product->sellingPrice());
        $this->assertCount(1, $product->addons);
        $this->assertEquals('Extra cheese', $product->addons->first()->name);
        $this->assertTrue($product->attributeValues()->where('attribute_values.id', $mild->id)->exists());
    }

    public function test_product_rejects_discount_above_price(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Bad Deal',
            'price' => 5.00,
            'discount_price' => 6.00,
        ]);

        $response->assertSessionHasErrors('discount_price');
    }

    public function test_banner_crud(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('admin.banners.store'), [
            'title' => 'Weekend Sale',
            'redirect_type' => 'url',
            'redirect_target' => 'https://example.com/sale',
            'position' => 'home',
        ])->assertRedirect(route('admin.banners.index'));

        $banner = \App\Models\Banner::where('title', 'Weekend Sale')->firstOrFail();
        $this->assertEquals('url', $banner->redirect_type);

        $this->actingAs($admin)->delete(route('admin.banners.destroy', $banner))
            ->assertRedirect(route('admin.banners.index'));

        $this->assertNull($banner->fresh());
    }
}
