<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Models\Role;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SettingController extends Controller
{
    public function index()
    {
        $settings = SchoolSetting::all()->keyBy('key');
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $fields = [
            'school_name', 'school_motto', 'school_box', 'school_town', 'school_address',
            'school_phone', 'school_email', 'currency_symbol', 'admission_prefix',
            'mpesa_paybill', 'mpesa_account_name', 'sms_sender_id'
        ];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                SchoolSetting::set($field, $request->input($field));
            }
        }

        AuditLog::record(action: 'settings_updated', userId: Auth::id());

        return back()->with('success', 'School settings updated successfully.');
    }

    public function usersIndex()
    {
        $users = User::with('roles')->orderBy('name')->paginate(15);
        $roles = Role::all();

        return view('settings.users', compact('users', 'roles'));
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role_name' => ['required', 'exists:roles,name'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'role_name' => $validated['role_name'],
            'password' => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        $role = Role::where('name', $validated['role_name'])->first();
        if ($role) {
            $user->roles()->sync([$role->id]);
        }

        AuditLog::record(action: 'user_created', model: $user, userId: Auth::id());

        return back()->with('success', "Staff account for {$user->name} created successfully.");
    }

    public function toggleUserStatus(int $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return back()->withErrors(['error' => 'You cannot deactivate your own account.']);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        AuditLog::record(
            action: $user->is_active ? 'user_activated' : 'user_deactivated',
            model: $user,
            userId: Auth::id()
        );

        $statusStr = $user->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "User account {$user->name} {$statusStr}.");
    }

    public function auditLogsIndex(Request $request)
    {
        $query = AuditLog::with('user');

        if ($action = $request->input('action')) {
            $query->where('action', 'LIKE', "%{$action}%");
        }

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        $logs = $query->orderByDesc('created_at')->paginate(25)->withQueryString();
        $users = User::orderBy('name')->get();

        return view('settings.audit-logs', compact('logs', 'users'));
    }
}

