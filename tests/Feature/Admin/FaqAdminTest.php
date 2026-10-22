<?php

use App\Enums\AdminRole;
use App\Filament\Resources\FaqCategories\FaqCategoryResource;
use App\Filament\Resources\FaqCategories\Pages\CreateFaqCategory;
use App\Filament\Resources\FaqCategories\Pages\ListFaqCategories;
use App\Filament\Resources\Faqs\FaqResource;
use App\Filament\Resources\Faqs\Pages\CreateFaq;
use App\Filament\Resources\Faqs\Pages\EditFaq;
use App\Filament\Resources\Faqs\Pages\ListFaqs;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Create an admin user with an exact FAQ permission set.
 *
 * @param  array<int, string>  $permissions
 */
function faqAdmin(array $permissions): User
{
    $role = Role::findOrCreate(AdminRole::CoAdmin->value, 'web');

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $role->syncPermissions($permissions);

    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

/**
 * The permissions a co-admin needs to manage both FAQ resources.
 *
 * @return array<int, string>
 */
function faqManagementPermissions(): array
{
    return [
        'ViewAny:Faq', 'View:Faq', 'Create:Faq', 'Update:Faq', 'Delete:Faq', 'DeleteAny:Faq',
        'ViewAny:FaqCategory', 'View:FaqCategory', 'Create:FaqCategory', 'Update:FaqCategory', 'Delete:FaqCategory', 'DeleteAny:FaqCategory',
    ];
}

test('a co-admin can manage FAQs and FAQ categories', function () {
    $admin = faqAdmin(faqManagementPermissions());
    $category = FaqCategory::factory()->create();

    $this->actingAs($admin)->get(FaqResource::getUrl('index'))->assertOk();
    $this->actingAs($admin)->get(FaqCategoryResource::getUrl('index'))->assertOk();

    Livewire::actingAs($admin)
        ->test(CreateFaqCategory::class)
        ->fillForm([
            'name' => 'Wawancara',
            'slug' => 'wawancara',
            'description' => 'Pertanyaan seputar tahap wawancara.',
            'sort_order' => 3,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('faq_categories', ['slug' => 'wawancara']);

    Livewire::actingAs($admin)
        ->test(CreateFaq::class)
        ->fillForm([
            'faq_category_id' => $category->id,
            'question' => 'Apa yang perlu disiapkan sebelum wawancara?',
            'answer' => '<p>Pelajari profil beasiswa dan latih jawabanmu.</p>',
            'is_published' => true,
            'is_featured' => true,
            'sort_order' => 2,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $faq = Faq::query()->where('question', 'Apa yang perlu disiapkan sebelum wawancara?')->firstOrFail();

    expect($faq->category->is($category))->toBeTrue()
        ->and($faq->is_published)->toBeTrue()
        ->and($faq->is_featured)->toBeTrue()
        ->and($faq->sort_order)->toBe(2)
        ->and($faq->published_at)->not->toBeNull()
        ->and($faq->answer)->toContain('Pelajari profil beasiswa');
});

test('an FAQ saved without publication stays a draft', function () {
    $admin = faqAdmin(faqManagementPermissions());
    $category = FaqCategory::factory()->create();

    Livewire::actingAs($admin)
        ->test(CreateFaq::class)
        ->fillForm([
            'faq_category_id' => $category->id,
            'question' => 'Pertanyaan yang masih draf',
            'answer' => '<p>Jawaban yang belum siap terbit.</p>',
            'sort_order' => 1,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $faq = Faq::query()->where('question', 'Pertanyaan yang masih draf')->firstOrFail();

    expect($faq->is_published)->toBeFalse()
        ->and($faq->published_at)->toBeNull();
});

test('a co-admin can update the featured flag and order from the editor', function () {
    $admin = faqAdmin(faqManagementPermissions());
    $faq = Faq::factory()->create([
        'question' => 'Pertanyaan awal',
        'is_featured' => false,
        'sort_order' => 0,
    ]);

    Livewire::actingAs($admin)
        ->test(EditFaq::class, ['record' => $faq->getRouteKey()])
        ->fillForm([
            'question' => 'Pertanyaan yang sudah diperbarui',
            'is_featured' => true,
            'sort_order' => 4,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($faq->refresh()->question)->toBe('Pertanyaan yang sudah diperbarui')
        ->and($faq->is_featured)->toBeTrue()
        ->and($faq->sort_order)->toBe(4);
});

test('the FAQ table can be searched and filtered by category and publication', function () {
    $admin = faqAdmin(faqManagementPermissions());
    $category = FaqCategory::factory()->create();
    $otherCategory = FaqCategory::factory()->create();

    $alpha = Faq::factory()->published()->create([
        'faq_category_id' => $category->id,
        'question' => 'Pertanyaan Alpha',
    ]);
    $beta = Faq::factory()->draft()->create([
        'faq_category_id' => $otherCategory->id,
        'question' => 'Pertanyaan Beta',
    ]);

    Livewire::actingAs($admin)
        ->test(ListFaqs::class)
        ->searchTable('Alpha')
        ->assertCanSeeTableRecords([$alpha])
        ->assertCanNotSeeTableRecords([$beta]);

    Livewire::actingAs($admin)
        ->test(ListFaqs::class)
        ->filterTable('faq_category_id', $category)
        ->assertCanSeeTableRecords([$alpha])
        ->assertCanNotSeeTableRecords([$beta]);

    Livewire::actingAs($admin)
        ->test(ListFaqs::class)
        ->filterTable('is_published', true)
        ->assertCanSeeTableRecords([$alpha])
        ->assertCanNotSeeTableRecords([$beta]);
});

test('the publish and unpublish actions change public visibility', function () {
    $admin = faqAdmin(faqManagementPermissions());
    $faq = Faq::factory()->draft()->create(['question' => 'FAQ untuk aksi terbit']);

    Livewire::actingAs($admin)
        ->test(ListFaqs::class)
        ->callAction(TestAction::make('publish')->table($faq))
        ->assertNotified();

    expect($faq->refresh()->is_published)->toBeTrue()
        ->and($faq->published_at)->not->toBeNull();

    Livewire::actingAs($admin)
        ->test(ListFaqs::class)
        ->callAction(TestAction::make('unpublish')->table($faq))
        ->assertNotified();

    expect($faq->refresh()->is_published)->toBeFalse();
});

test('an admin without FAQ permissions cannot open the FAQ resources', function () {
    $admin = faqAdmin([]);

    $this->actingAs($admin)->get(FaqResource::getUrl('index'))->assertForbidden();
    $this->actingAs($admin)->get(FaqResource::getUrl('create'))->assertForbidden();
    $this->actingAs($admin)->get(FaqCategoryResource::getUrl('index'))->assertForbidden();
});

test('the category list reports how many FAQs use a category', function () {
    $admin = faqAdmin(faqManagementPermissions());
    $category = FaqCategory::factory()->create(['name' => 'Layanan & Premium']);
    Faq::factory()->count(2)->create(['faq_category_id' => $category->id]);

    Livewire::actingAs($admin)
        ->test(ListFaqCategories::class)
        ->assertCanSeeTableRecords([$category])
        ->assertSee('Layanan & Premium');
});
