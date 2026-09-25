<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
 * DDE-Mart Admin — ops settings editor (original controller).
 * Only keys declared in config/settings.php are editable; gateway secrets are
 * NOT settings — they live in .env (legacy stored them in Firestore docs).
 */
class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.content.settings.form', [
            'groups' => config('settings.groups'),
            'values' => Setting::allMerged(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $allowed = collect(config('settings.groups'))->flatMap(fn ($g) => $g['keys'])->all();

        $validated = $request->validate([
            'settings' => ['nullable', 'array'],
            'settings.*' => ['nullable', 'string', 'max:500'],
        ]);

        foreach ($validated['settings'] ?? [] as $key => $value) {
            if (in_array($key, $allowed, true)) {
                Setting::set($key, $value);
            }
        }

        return redirect()->route('admin.settings.edit')->with('success', 'Settings saved.');
    }
}
