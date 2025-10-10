<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Users list.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $users = User::with('role')
            ->when($search !== '', function ($query) use ($search) {
                $normalized = mb_strtolower($search, 'UTF-8');
                $like = '%' . $normalized . '%';
                $query->where(function ($inner) use ($like, $search, $normalized) {
                    $inner->whereRaw('LOWER(first_name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(last_name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$like])
                        ->orWhere('phone', 'like', '%' . $search . '%');

                    if (ctype_digit($search)) {
                        $inner->orWhere('id', (int) $search);
                    }
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin::Users.index', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    /**
     * Show user.
     */
    public function show($id)
    {
        $user = User::with(['role', 'resumes'])->findOrFail($id);
        return view('admin::Users.show', compact('user'));
    }
}
