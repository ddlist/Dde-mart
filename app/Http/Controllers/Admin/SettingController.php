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
 * Keys carry a type (text/number/bool/select/file) rendered by the form.
 */
class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.content.settings.form', [
            'groups' => config('settings.groups'),
            'types' => config('settings.types', []),
            'values' => Setting::allMerged(),
            'integrations' => $this->integrations(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $groups = config('settings.groups');
        $types = config('settings.types', []);
        $allowed = collect($groups)->flatMap(fn ($g) => $g['keys'])->all();

        $validated = $request->validate([
            'settings' => ['nullable', 'array'],
            'settings.*' => ['nullable', 'string', 'max:500'],
        ]);

        $input = $validated['settings'] ?? [];

        foreach ($allowed as $key) {
            $type = $types[$key]['type'] ?? 'text';

            if ($type === 'display') {
                continue;
            }

            if ($type === 'file') {
                $kind = $types[$key]['kind'] ?? 'image';
                $rules = [$kind === 'audio' ? 'mimes:mp3,ogg,wav' : 'image',
                    'max:2048'];
                if ($request->hasFile("files.{$key}")) {
                    $request->validate(["files.{$key}" => $rules]);
                    $path = $request->file("files.{$key}")
                        ->store('settings', 'public');
                    Setting::set($key, $path);
                }
                continue;
            }

            if ($type === 'bool') {
                Setting::set($key, array_key_exists($key, $input) ? '1' : '0');
                continue;
            }

            if (! array_key_exists($key, $input)) {
                continue;
            }

            $value = $input[$key];

            if ($type === 'number' && $value !== null && $value !== '' && ! is_numeric($value)) {
                continue;
            }

            if ($type === 'select') {
                $options = array_keys($types[$key]['options'] ?? []);
                if ($options !== [] && ! in_array($value, $options, true)) {
                    continue;
                }
            }

            Setting::set($key, $value);
        }

        return redirect()->route('admin.settings.edit')->with('success', 'Settings saved.');
    }

    /** Read-only integration health: presence only, never secret values. */
    protected function integrations(): array
    {
        $firebase = env('FIREBASE_CREDENTIALS', '');

        return [
            'Stripe' => (bool) env('STRIPE_SECRET'),
            'Razorpay' => (bool) env('RAZORPAY_KEY'),
            'PayPal' => (bool) env('PAYPAL_CLIENT_ID'),
            'Paytm' => (bool) env('PAYTM_MERCHANT_ID'),
            'FCM service file' => $firebase !== '' && (
                is_file($firebase)
                || is_file(storage_path('app/'.ltrim($firebase, '/')))
                || is_file(base_path($firebase))
            ),
            'Mail host' => (bool) env('MAIL_HOST'),
            'Maps key' => (bool) env('MAPS_KEY'),
        ];
    }
}
