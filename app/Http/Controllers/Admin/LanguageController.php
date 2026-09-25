<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveLanguageRequest;
use App\Models\Language;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/* DDE-Mart Admin — languages (original controller). Exactly one default. */
class LanguageController extends Controller
{
    public function index(): View
    {
        $languages = Language::orderByDesc('is_default')->orderBy('code')->paginate(15);

        return view('admin.content.languages.index', compact('languages'));
    }

    public function create(): View
    {
        return view('admin.content.languages.form', $this->formData(new Language()));
    }

    public function store(SaveLanguageRequest $request): RedirectResponse
    {
        $language = Language::create($this->payload($request));
        $this->ensureSingleDefault($language);

        return redirect()->route('admin.languages.index')->with('success', "Language '{$language->name}' created.");
    }

    public function edit(Language $language): View
    {
        return view('admin.content.languages.form', $this->formData($language));
    }

    public function update(SaveLanguageRequest $request, Language $language): RedirectResponse
    {
        $language->update($this->payload($request, $language));
        $this->ensureSingleDefault($language);

        return redirect()->route('admin.languages.index')->with('success', "Language '{$language->name}' updated.");
    }

    public function destroy(Language $language): RedirectResponse
    {
        if ($language->is_default) {
            return redirect()->route('admin.languages.index')
                ->with('error', 'Cannot delete the default language. Set another default first.');
        }

        $language->delete();

        return redirect()->route('admin.languages.index')->with('success', "Language '{$language->name}' deleted.");
    }

    protected function formData(Language $language): array
    {
        return [
            'language' => $language,
            'method' => $language->exists ? 'PUT' : 'POST',
            'action' => $language->exists
                ? route('admin.languages.update', $language)
                : route('admin.languages.store'),
        ];
    }

    protected function payload(SaveLanguageRequest $request): array
    {
        $data = $request->validated();
        $data['code'] = strtolower($data['code']);
        $data['is_default'] = $request->boolean('is_default');
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    protected function ensureSingleDefault(Language $language): void
    {
        if ($language->is_default) {
            Language::where('id', '!=', $language->id)->update(['is_default' => false]);
        } elseif (! Language::where('is_default', true)->exists()) {
            $language->update(['is_default' => true]);
        }
    }
}
