<?php

namespace App\Http\Controllers;

use App\Services\AdminSystemNotifications;
use Illuminate\Http\Request;

class BusinessProfileController extends Controller
{
    public function edit(Request $request)
    {
        $business = $request->user()->business;
        $profileIncomplete = ! $business || ! $business->hasProfileDetails();
        return view('business.edit', [
            'business' => $business,
            'editing' => $request->boolean('edit') || ($profileIncomplete && ! $request->has('edit')) || session('errors'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'owner_name' => ['required', 'string', 'max:255'],
            'business_type' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
        unset($data['logo']);

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $name = 'business-logo-' . $request->user()->id . '-' . \Illuminate\Support\Str::uuid() . '.' . $file->guessExtension();
            $logoDirectory = public_path('uploads/logos');

            if (! is_dir($logoDirectory)) {
                mkdir($logoDirectory, 0755, true);
            }

            $file->move($logoDirectory, $name);
            $data['logo'] = 'uploads/logos/' . $name;
        }

        $existing = $request->user()->business;
        $before = $existing ? $existing->only(['business_name', 'owner_name', 'business_type', 'address', 'contact_number', 'email', 'logo', 'description']) : [];
        $business = $request->user()->business()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $data
        );
        $request->user()->setRelation('business', $business);
        $after = $business->only(array_keys($before));
        if (! $existing || collect($before)->contains(fn ($value, $key) => (string) ($value ?? '') !== (string) ($after[$key] ?? ''))) {
            app(AdminSystemNotifications::class)->record($request->user(), 'Business profile updated', 'Business information was updated.');
        }

        return redirect()
            ->route('business.edit')
            ->with('status', 'Business information saved successfully.');
    }
}
