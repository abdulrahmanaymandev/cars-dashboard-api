<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::orderBy('id')->get()->map(fn(User $u) => $this->format($u));
        return response()->json(['success' => true, 'data' => $users]);
    }

    public function store(Request $request): JsonResponse
    {
        $v = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role'     => ['required', Rule::in(['admin', 'manager', 'sales', 'Admin', 'Manager', 'Sales'])],
            'status'   => ['sometimes', Rule::in(['active', 'inactive'])],
        ]);

        $user = User::create([
            'name'     => $v['name'],
            'email'    => $v['email'],
            'password' => Hash::make($v['password']),
            'role'     => strtolower($v['role']),
            'status'   => $v['status'] ?? 'active',
        ]);

        return response()->json(['success' => true, 'data' => $this->format($user)], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $v = $request->validate([
            'name'     => ['sometimes', 'string', 'max:255'],
            'email'    => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['sometimes', 'string', 'min:6'],
            'role'     => ['sometimes', Rule::in(['admin', 'manager', 'sales', 'Admin', 'Manager', 'Sales'])],
            'status'   => ['sometimes', Rule::in(['active', 'inactive'])],
        ]);

        if (isset($v['password'])) {
            $v['password'] = Hash::make($v['password']);
        }
        if (isset($v['role'])) {
            $v['role'] = strtolower($v['role']);
        }

        $user->update($v);

        return response()->json(['success' => true, 'data' => $this->format($user)]);
    }

    public function destroy(User $user): JsonResponse
    {
        // Prevent deleting the currently authenticated user
        if ($user->id === request()->user()->id) {
            return response()->json(['success' => false, 'message' => 'Cannot delete your own account.'], 403);
        }

        $user->delete();
        return response()->json(['success' => true, 'message' => 'User deleted.']);
    }

    private function format(User $u): array
    {
        return [
            'id'     => $u->id,
            'name'   => $u->name,
            'email'  => $u->email,
            'role'   => ucfirst($u->role),
            'status' => $u->status,
        ];
    }
}
