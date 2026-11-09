<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    private const ADMIN_ROLE      = 'admin';
    private const STATUS_ACTIVE   = 'active';
    private const STATUS_INACTIVE = 'inactive';

    // List + search
    public function index(Request $request)
    {
        $members = Member::withCount('borrowings')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;

                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                      ->orWhere('member_code', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('member_id')
            ->paginate(10)
            ->withQueryString();

        $totalMembers = Member::count();

        // Admin accounts are only loaded for admins
        $isAdmin = $this->isAdmin();

        $admins = $isAdmin
            ? User::where('role', self::ADMIN_ROLE)->orderBy('user_id')->get()
            : collect();

        $activeAdmins = $admins
            ->filter(fn ($a) => strtolower((string) $a->status) === self::STATUS_ACTIVE)
            ->count();

        return view('members.index', compact('members', 'totalMembers', 'isAdmin', 'admins', 'activeAdmins'));
    }

    // Member info + borrowed books
    public function show(Member $member)
    {
        $borrowings = $member->borrowings()
            ->with('book')
            ->orderByDesc('borrowing_date')
            ->get();

        // Collapse copies borrowed in the same transaction
        // (same book, same borrow date, same due date) into one entry.
        // This is the same grouping key the view uses.
        $transactions = $borrowings->groupBy(fn ($b) =>
            $b->book_id . '|' .
            optional($b->borrowing_date)->toDateString() . '|' .
            optional($b->due_date)->toDateString()
        );

        $stats = [
            'total'    => $transactions->count(),
            'active'   => $transactions->filter(fn ($g) => $g->first()->display_status === 'Borrowed')->count(),
            'overdue'  => $transactions->filter(fn ($g) => $g->first()->display_status === 'Overdue')->count(),
            'returned' => $transactions->filter(fn ($g) => $g->first()->display_status === 'Returned')->count(),
        ];

        return view('members.show', compact('member', 'borrowings', 'stats'));
    }

    public function store(Request $request)
    {
        // Admin account actions share this route (see handleAdminAction)
        if ($request->filled('admin_action')) {
            return $this->handleAdminAction($request);
        }

        // No member_code here: it is generated automatically
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255', 'unique:members,email'],
            'status'    => ['required', Rule::in(['active', 'inactive'])],
        ]);

        // Retry a few times in case two requests pick the same code at once
        $member = null;

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                $member = Member::create($data + [
                    'member_code' => $this->nextMemberCode(),
                ]);
                break;
            } catch (QueryException $e) {
                if ($attempt === 2) {
                    throw $e;
                }
            }
        }

        return redirect()->route('members')
            ->with('members_success', "Member registered successfully. Code: {$member->member_code}");
    }

    public function update(Request $request, Member $member)
    {
        // member_code is not editable, so it is not validated or saved here
        $request->validateWithBag('editMember', [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('members', 'email')->ignore($member->member_id, 'member_id'),
            ],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        // Cannot deactivate a member who still has borrowed or overdue books
        if ($request->status === 'inactive' && $member->status !== 'inactive') {
            $hasOpenBorrowings = $member->borrowings()
                ->get()
                ->contains(fn ($b) => in_array($b->display_status, ['Borrowed', 'Overdue']));

            if ($hasOpenBorrowings) {
                return redirect()->route('members')
                    ->with('members_error', 'Cannot set this member to inactive because they still have borrowed or overdue books.');
            }
        }

        $member->update($request->only('full_name', 'email', 'status'));

        return redirect()->route('members')
            ->with('members_success', 'Member updated successfully.');
    }

    public function destroy(Member $member)
    {
        // Keep the borrowing history intact
        if ($member->borrowings()->exists()) {
            return redirect()->route('members')
                ->with('members_error', 'Cannot delete this member because they have borrowing records.');
        }

        $member->delete();

        return redirect()->route('members')
            ->with('members_success', 'Member deleted successfully.');
    }

    // ==========================================
    // Admin account actions (create / toggle / reset)
    // ==========================================

    private function handleAdminAction(Request $request)
    {
        // Only admins may manage admin accounts
        abort_unless($this->isAdmin(), 403);

        return match ($request->input('admin_action')) {
            'create' => $this->createAdmin($request),
            'toggle' => $this->toggleAdmin($request),
            'reset'  => $this->resetAdminPassword($request),
            default  => abort(422),
        };
    }

    private function createAdmin(Request $request)
    {
        $request->merge(['username' => trim((string) $request->input('username', ''))]);

        $data = $request->validateWithBag('addAdmin', [
            'username' => [
                'required', 'string', 'min:4', 'max:50', 'alpha_dash',
                Rule::unique('users', 'username'),
            ],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
        ]);

        User::create([
            'username' => $data['username'],
            'password' => $data['password'], // hashed by the model cast
            'role'     => self::ADMIN_ROLE,
            'status'   => self::STATUS_ACTIVE,
        ]);

        return redirect()->route('members')
            ->with('admins_success', "Admin \"{$data['username']}\" created.");
    }

    private function toggleAdmin(Request $request)
    {
        $user = $this->findAdmin($request);

        if ($user->user_id === auth()->id()) {
            return redirect()->route('members')
                ->with('admins_error', 'You cannot deactivate your own account.');
        }

        $isActive = strtolower((string) $user->status) === self::STATUS_ACTIVE;

        // Never lock everyone out
        if ($isActive) {
            $otherActive = User::where('role', self::ADMIN_ROLE)
                ->where('status', self::STATUS_ACTIVE)
                ->where('user_id', '!=', $user->user_id)
                ->exists();

            if (! $otherActive) {
                return redirect()->route('members')
                    ->with('admins_error', 'At least one active admin is required.');
            }
        }

        $user->update([
            'status' => $isActive ? self::STATUS_INACTIVE : self::STATUS_ACTIVE,
        ]);

        return redirect()->route('members')->with(
            'admins_success',
            "Admin \"{$user->username}\" " . ($isActive ? 'deactivated.' : 'activated.')
        );
    }

    private function resetAdminPassword(Request $request)
    {
        $user = $this->findAdmin($request);

        $request->validateWithBag('resetPassword', [
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
        ]);

        $user->update(['password' => $request->input('password')]);

        return redirect()->route('members')
            ->with('admins_success', "Password for \"{$user->username}\" was reset.");
    }

    // Find the admin account named in the form, or 404
    private function findAdmin(Request $request): User
    {
        return User::where('role', self::ADMIN_ROLE)
            ->findOrFail((int) $request->input('admin_user_id'));
    }

    private function isAdmin(): bool
    {
        return auth()->check()
            && strtolower((string) auth()->user()->role) === self::ADMIN_ROLE;
    }

    // ==========================================
    // Helpers
    // ==========================================

    // Builds the next code: MC-0001, MC-0002, ...
    private function nextMemberCode(): string
    {
        $next = ((int) Member::max('member_id')) + 1;

        return 'MC-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}