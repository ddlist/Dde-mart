<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SavePageRequest;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/* DDE-Mart Admin — CMS pages (original controller). */
class PageController extends Controller
{
    public function index(): View
    {
        $pages = Page::orderBy('name')->paginate(15);

        return view('admin.content.pages.index', compact('pages'));
    }

    public function create(): View
    {
        return view('admin.content.pages.form', $this->formData(new Page()));
    }

    public function store(SavePageRequest $request): RedirectResponse
    {
        $page = Page::create($this->payload($request));

        return redirect()->route('admin.pages.index')->with('success', "Page '{$page->name}' created.");
    }

    public function edit(Page $page): View
    {
        return view('admin.content.pages.form', $this->formData($page));
    }

    public function update(SavePageRequest $request, Page $page): RedirectResponse
    {
        $page->update($this->payload($request));

        return redirect()->route('admin.pages.index')->with('success', "Page '{$page->name}' updated.");
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        return redirect()->route('admin.pages.index')->with('success', "Page '{$page->name}' deleted.");
    }

    protected function formData(Page $page): array
    {
        return [
            'page' => $page,
            'method' => $page->exists ? 'PUT' : 'POST',
            'action' => $page->exists
                ? route('admin.pages.update', $page)
                : route('admin.pages.store'),
        ];
    }

    protected function payload(SavePageRequest $request): array
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
