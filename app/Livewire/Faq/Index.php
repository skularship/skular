<?php

namespace App\Livewire\Faq;

use App\Models\Faq;
use App\Models\FaqCategory;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The public FAQ centre.
 *
 * Every read goes through the `published` scope, so drafts and FAQs in
 * inactive categories cannot appear in the list, a search result, a category
 * filter, or a paginated page. The FAQPage JSON-LD is built from the same
 * rendered page of results and handed to the layout's <head>.
 */
#[Title('FAQ Persiapan Beasiswa: Esai dan Wawancara')]
#[Layout('layouts.public')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    #[Validate('nullable|string|max:100')]
    public string $search = '';

    #[Url(as: 'kategori', except: '')]
    public string $category = '';

    /**
     * Keep the search input within the validated limit as the visitor types.
     */
    public function updatedSearch(): void
    {
        $this->search = Str::limit(trim($this->search), 100, '');
        $this->validateOnly('search');
        $this->resetPage();
    }

    /**
     * Apply a category chosen from the public filter bar.
     */
    public function selectCategory(string $slug): void
    {
        $this->category = FaqCategory::query()
            ->active()
            ->where('slug', $slug)
            ->exists()
                ? $slug
                : '';

        $this->resetPage();
    }

    /**
     * Return to the complete list of published questions.
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->category = '';
        $this->resetPage();
    }

    /**
     * Render the public FAQ page (route faq.index).
     *
     * The FAQPage structured data reuses the paginated rows the view shows, so
     * it never lists a question that is not visible and costs no extra query.
     * Livewire only renders the layout on the first (crawler) request.
     *
     * @return View The livewire.faq.index view.
     */
    public function render(): View
    {
        $categories = FaqCategory::query()
            ->active()
            ->ordered()
            ->whereHas('faqs', fn (Builder $query): Builder => $query->published())
            ->withCount([
                'faqs' => fn (Builder $query): Builder => Faq::applyPublished($query),
            ])
            ->get();

        // An unknown or retired category in the URL simply falls back to the
        // complete list instead of exposing an empty, misleading filter state.
        $activeCategory = $categories->firstWhere('slug', $this->category);
        $categorySlug = $activeCategory?->slug;

        $faqs = Faq::query()
            ->published()
            ->with('category')
            ->search($this->search)
            ->when($categorySlug !== null, fn (Builder $query): Builder => $query->inCategory($categorySlug))
            ->ordered()
            ->paginate(10)
            ->withQueryString();

        return view('livewire.faq.index', [
            'faqs' => $faqs,
            'categories' => $categories,
            'activeCategory' => $activeCategory,
        ])->layoutData([
            'structuredData' => Seo::faqPage($faqs->getCollection()),
        ]);
    }
}
