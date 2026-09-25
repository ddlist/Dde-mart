<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/*
 * DDE-Mart Admin — manual email composer (original controller).
 * Renders a template with pasted values and sends via the configured mailer
 * (log driver locally). Every send is validated, never raw.
 */
class EmailController extends Controller
{
    public function compose(Request $request): View
    {
        $templates = EmailTemplate::where('is_active', true)->orderBy('key')->get();
        $preview = null;

        if ($request->filled('template_id')) {
            $template = EmailTemplate::find($request->input('template_id'));

            if ($template) {
                $data = [];

                foreach (preg_split('/\r?\n/', (string) $request->input('values', '')) as $pair) {
                    if (str_contains($pair, '=')) {
                        [$k, $v] = explode('=', $pair, 2);
                        $data[trim($k)] = trim($v);
                    }
                }

                $preview = $template->render($data);
            }
        }

        return view('admin.content.compose', compact('templates', 'preview'));
    }

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'to' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string'],
        ]);

        Mail::raw($validated['body'], function ($message) use ($validated, $request) {
            $message->to($validated['to'])->subject($validated['subject']);
            $message->from(config('mail.from.address'), config('mail.from.name'));
        });

        \App\Models\Notification::create([
            'audience' => 'customer',
            'subject' => 'Email: '.$validated['subject'],
            'message' => 'To '.$validated['to'],
            'status' => 'sent',
            'sent_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.email.compose')->with('success', "Email queued to {$validated['to']}.");
    }
}
