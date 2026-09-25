<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveEmailTemplateRequest;
use App\Models\EmailTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/* DDE-Mart Admin — email templates (original controller). */
class EmailTemplateController extends Controller
{
    public function index(): View
    {
        $templates = EmailTemplate::orderBy('key')->paginate(15);

        return view('admin.content.emails.index', compact('templates'));
    }

    public function create(): View
    {
        return view('admin.content.emails.form', $this->formData(new EmailTemplate()));
    }

    public function store(SaveEmailTemplateRequest $request): RedirectResponse
    {
        $template = EmailTemplate::create($this->payload($request));

        return redirect()->route('admin.emails.index')->with('success', "Template '{$template->key}' created.");
    }

    public function edit(EmailTemplate $email): View
    {
        return view('admin.content.emails.form', $this->formData($email));
    }

    public function update(SaveEmailTemplateRequest $request, EmailTemplate $email): RedirectResponse
    {
        $email->update($this->payload($request));

        return redirect()->route('admin.emails.index')->with('success', "Template '{$email->key}' updated.");
    }

    public function destroy(EmailTemplate $email): RedirectResponse
    {
        $email->delete();

        return redirect()->route('admin.emails.index')->with('success', "Template '{$email->key}' deleted.");
    }

    protected function formData(EmailTemplate $template): array
    {
        return [
            'template' => $template,
            'method' => $template->exists ? 'PUT' : 'POST',
            'action' => $template->exists
                ? route('admin.emails.update', $template)
                : route('admin.emails.store'),
        ];
    }

    protected function payload(SaveEmailTemplateRequest $request): array
    {
        $data = $request->validated();
        $data['send_to_admin'] = $request->boolean('send_to_admin');
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
