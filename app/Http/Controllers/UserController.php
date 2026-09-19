<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->latest()
            ->paginate(10);

        return view('backend.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('backend.users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'secondary_phone' => ['nullable', 'string', 'max:50'],
            'whatsapp_phone' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'avatar_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
            'remove_avatar' => ['nullable'],
            'permission' => ['required', 'integer', 'in:1,2,3,4,5,6'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $avatarPath = null;

        if ($request->hasFile('avatar_file')) {
            $avatarPath = $this->storeAvatar($request->file('avatar_file'));
        }

        User::create([
            'name' => $validated['name'],
            'job_title' => $validated['job_title'] ?? null,
            'bio' => $validated['bio'] ?? null,
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'secondary_phone' => $validated['secondary_phone'] ?? null,
            'whatsapp_phone' => $validated['whatsapp_phone'] ?? null,
            'avatar' => $avatarPath,
            'permission' => $validated['permission'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('backend.users.index')
            ->with('success', 'Them user thanh cong.');
    }

    public function edit(User $user): View
    {
        return view('backend.users.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'secondary_phone' => ['nullable', 'string', 'max:50'],
            'whatsapp_phone' => ['nullable', 'string', 'max:50'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'avatar_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
            'remove_avatar' => ['nullable'],
            'permission' => ['required', 'integer', 'in:1,2,3,4,5,6'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ]);

        $avatarPath = $user->avatar;

        if ($request->boolean('remove_avatar') && $avatarPath) {
            $this->deleteAvatarIfExists($avatarPath);
            $avatarPath = null;
        }

        if ($request->hasFile('avatar_file')) {
            $this->deleteAvatarIfExists($avatarPath);
            $avatarPath = $this->storeAvatar($request->file('avatar_file'));
        }

        $data = [
            'name' => $validated['name'],
            'job_title' => $validated['job_title'] ?? null,
            'bio' => $validated['bio'] ?? null,
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'secondary_phone' => $validated['secondary_phone'] ?? null,
            'whatsapp_phone' => $validated['whatsapp_phone'] ?? null,
            'avatar' => $avatarPath,
            'permission' => $validated['permission'],
            'email' => $validated['email'],
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        return redirect()
            ->route('backend.users.index')
            ->with('success', 'Cap nhat user thanh cong.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if (auth()->id() === $user->id) {
            return redirect()
                ->route('backend.users.index')
                ->with('error', 'Khong the xoa tai khoan dang dang nhap.');
        }

        $this->deleteAvatarIfExists($user->avatar);
        $user->delete();

        return redirect()
            ->route('backend.users.index')
            ->with('success', 'Xoa user thanh cong.');
    }

    protected function storeAvatar($file): string
    {
        $directory = public_path('uploads/users');

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filename = 'avatar-' . Str::random(20) . '.' . strtolower($file->getClientOriginalExtension());
        $file->move($directory, $filename);

        return 'uploads/users/' . $filename;
    }

    protected function deleteAvatarIfExists(?string $path): void
    {
        if (! $path || ! Str::startsWith($path, 'uploads/users/')) {
            return;
        }

        $fullPath = public_path($path);

        if (File::exists($fullPath)) {
            File::delete($fullPath);
        }
    }
}
