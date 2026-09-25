<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SavePushTemplateRequest;
use App\Models\NotificationTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/* DDE-Mart Admin — automated push templates (original controller). */
class PushTemplateController extends Controller
{
    public function index(): View
    {
        $templates = NotificationTemplate::orderBy('key')->paginate(15);

        return view('admin.content.push.index', compact('templates'));
    }

    public function create(): View
    {
        return view('admin.content.push.form', $this->formData(new NotificationTemplate()));
    }

    public function store(SavePushTemplateRequest $request): RedirectResponse
    {
        $template = NotificationTemplate::create($this->payload($request));

        return redirect()->route('admin.push.index')->with('success', "Template '{$template->key}' created.");
    }

    public function edit(NotificationTemplate $push): View
    {
        return view('admin.content.push.form', $this->formData($push));
    }

    public function update(SavePushTemplateRequest $request, NotificationTemplate $push): RedirectResponse
    {
        $push->update($this->payload($request));

        return redirect()->route('admin.push.index')->with('success', "Template '{$push->key}' updated.");
    }

    public function destroy(NotificationTemplate $push): RedirectResponse
    {
        $push->delete();

        return redirect()->route('admin.push.index')->with('success', "Template '{$push->key}' deleted.");
    }

    protected function formData(NotificationTemplate $template): array
    {
        return [
            'template' => $template,
            'method' => $template->exists ? 'PUT' : 'POST',
            'action' => $template->exists
                ? route('admin.push.update', $template)
                : route('admin.push.store'),
        ];
    }

    protected function payload(SavePushTemplateRequest $request): array
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
