{{-- DDE-Mart Admin — manual email composer (original view, UI kit) --}}
<x-admin-layout title="Send Email">
    <x-page-head title="Send Email" sub="Render a template, then send via the mailer." />

    <form method="GET" action="{{ route('admin.email.compose') }}" class="max-w-2xl space-y-5" id="compose-form">
        @csrf
        <x-card title="Compose">
            <div class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Template" for="em-tpl">
                        <x-select id="em-tpl" name="template_id" onchange="document.getElementById('compose-form').submit()">
                            <option value="">— Blank —</option>
                            @foreach ($templates as $template)
                                <option value="{{ $template->id }}" @selected((string) request('template_id') === (string) $template->id)>{{ $template->key }}</option>
                            @endforeach
                        </x-select>
                    </x-field>
                    <x-field label="To" for="em-to" :error="$errors->first('to')">
                        <x-input id="em-to" name="to" type="email" required value="{{ old('to') }}" />
                    </x-field>
                </div>
                <x-field label="Values (key=value per line → preview)" for="em-vals">
                    <x-textarea id="em-vals" name="values" rows="2">{{ request('values') }}</x-textarea>
                </x-field>
                <x-field label="Subject" for="em-sub" :error="$errors->first('subject')">
                    <x-input id="em-sub" name="subject" required value="{{ old('subject', $preview['subject'] ?? '') }}" />
                </x-field>
                <x-field label="Body" for="em-body" :error="$errors->first('body')">
                    <x-textarea id="em-body" name="body" rows="8" required>{{ old('body', $preview['body'] ?? '') }}</x-textarea>
                </x-field>
            </div>
        </x-card>

        <div class="flex gap-2">
            <x-btn type="submit" formaction="{{ route('admin.email.send') }}" formmethod="POST">Send email</x-btn>
            <x-btn variant="ghost" type="submit">Preview template</x-btn>
        </div>
    </form>
</x-admin-layout>
