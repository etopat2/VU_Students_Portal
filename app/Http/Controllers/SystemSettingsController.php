<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class SystemSettingsController extends Controller
{
    public function edit(Request $request): View|Response
    {
        $institution = Institution::query()->first() ?? new Institution([
            'name' => 'Victoria University',
            'code' => 'VU',
        ]);
        $page = view('admin.settings', compact('institution'));

        if ($request->ajax()) {
            return response($page->renderSections()['content'])
                ->header('X-Page-Title', 'System settings');
        }

        return $page;
    }

    public function update(Request $request): RedirectResponse
    {
        $institution = Institution::query()->first();
        $codeRule = Rule::unique('institutions', 'code');

        if ($institution) {
            $codeRule->ignore($institution);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:30',
                $codeRule,
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        if ($institution) {
            $institution->update($validated);
        } else {
            Institution::create($validated);
        }

        ToastMagic::success('Institution settings saved.');

        return redirect()->route('system-settings.edit');
    }
}
