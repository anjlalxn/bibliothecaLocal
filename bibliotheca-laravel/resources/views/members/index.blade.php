@extends('layouts.panel')

@section('title', 'Members')
@section('heading', 'Members')

@section('content')
@php
// True when the last submit was a failed EDIT, so the add form isn't pre-filled with it
$editFailed = $errors->editMember->any();

// Failed admin forms (named error bags)
$addAdminFailed = $errors->addAdmin->any();
$resetFailed = $errors->resetPassword->any();

// Popup message (success or error), members or admins
$popupSuccess = session('members_success') ?? session('admins_success');
$popupError = session('members_error') ?? session('admins_error');

// Admin whose password reset just failed (so the modal can name them)
$resetAdmin = $resetFailed
? $admins->firstWhere('user_id', (int) old('admin_user_id'))
: null;
@endphp


<div class="stat-panel">
    <div class="row row-cols-1 row-cols-sm-2 {{ $isAdmin ? 'row-cols-xl-3' : '' }} g-3">
        <div class="col">
            <div class="stat-card stat-card--teal">
                <i class="bi bi-person-fill stat-icon"></i>
                <div class="stat-body">
                    <p class="stat-label">Total Members</p>
                    <p class="stat-value">{{ number_format($totalMembers) }}</p>
                </div>
            </div>
        </div>
        <div class="col">
            <a href="#" class="stat-card stat-card--cyan" data-bs-toggle="modal" data-bs-target="#addMember">
                <i class="bi bi-plus-circle stat-icon"></i>
                <div class="stat-body">
                    <p class="stat-label">Action</p>
                    <p class="stat-value">Register a Member</p>
                </div>
                <i class="bi bi-chevron-right stat-arrow"></i>
            </a>
        </div>
        @if ($isAdmin)
        <div class="col">
            <a href="#" class="stat-card bg-dark text-white text-decoration-none" data-bs-toggle="modal" data-bs-target="#addAdmin">
                <i class="bi bi-person-plus stat-icon"></i>
                <div class="stat-body">
                    <p class="stat-label">Action</p>
                    <p class="stat-value">Add New Admin</p>
                </div>
                <i class="bi bi-chevron-right stat-arrow"></i>
            </a>
        </div>
        @endif
    </div>
</div>


<section class="section-block">
    <div class="d-flex flex-column-reverse flex-sm-row justify-content-between align-items-sm-center gap-3">
        <div class="search-toolbar d-flex align-items-center gap-2">
            <form class="search-group input-group" action="{{ route('members') }}" method="GET" role="search">
                <input type="search" class="form-control" name="search" value="{{ request('search') }}"
                    placeholder="Name/Code/Email" aria-label="Search members">
                <button class="btn btn-info text-white" type="submit">
                    <i class="bi bi-search me-1"></i> Search
                </button>
            </form>
        </div>
        <div class="text-sm-end">
            <h2 class="section-title">All Members</h2>
            <p class="section-subtitle">Click to view member information</p>

        </div>
    </div>

    <div class="table-responsive">
        <table class="table data-table">
            <thead>
                <tr>
                    <th scope="col">Member Code</th>
                    <th scope="col">Full Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Borrowed</th>
                    <th scope="col">Status</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($members as $member)
                <tr>
                    <td>{{ $member->member_code }}</td>
                    <td>
                        <a href="{{ route('members.show', $member->member_id) }}" class="text-decoration-none fw-semibold">
                            {{ $member->full_name }}
                        </a>
                    </td>
                    <td>{{ $member->email }}</td>
                    <td>{{ $member->borrowings_count }}</td>
                    <td>
                        @if (strtolower((string) $member->status) === 'active')
                        <span class="badge text-bg-success">Active</span>
                        @else
                        <span class="badge text-bg-secondary">{{ ucfirst((string) $member->status) ?: 'Inactive' }}</span>
                        @endif
                    </td>
                    <td class="text-nowrap">
                        
                        <a href="{{ route('members.show', $member->member_id) }}"
                            class="btn btn-sm btn-outline-primary" aria-label="View member">
                            <i class="bi bi-eye"></i>
                        </a>

                        
                        <button type="button"
                            class="btn btn-sm btn-outline-success edit-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#editMember"
                            data-member-id="{{ $member->member_id }}"
                            data-url="{{ route('members.update', $member->member_id) }}"
                            data-code="{{ $member->member_code }}"
                            data-name="{{ $member->full_name }}"
                            data-email="{{ $member->email }}"
                            data-status="{{ strtolower((string) $member->status) }}"
                            aria-label="Edit member">
                            <i class="bi bi-pencil-square"></i>
                        </button>

                        
                        <button type="button"
                            class="btn btn-sm btn-outline-danger delete-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#deleteMember"
                            data-name="{{ $member->full_name }}"
                            data-url="{{ route('members.destroy', $member->member_id) }}"
                            aria-label="Delete member">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="table-empty">
                        <i class="bi bi-inbox"></i>
                        @if (request('search'))
                        No members match "{{ request('search') }}".
                        @else
                        No members to display yet.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $members->links() }}
    </div>
</section>

@if ($isAdmin)

<section class="section-block">
    <div class="d-flex flex-column-reverse flex-sm-row justify-content-between align-items-sm-center gap-3">
        <div>
            <span class="badge text-bg-dark">
                <i class="bi bi-person-badge me-1"></i> {{ $activeAdmins }} active
            </span>
        </div>
        <div class="text-sm-end">
            <h2 class="section-title">Admins</h2>
            <p class="section-subtitle">Accounts that can log in</p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table data-table">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Username</th>
                    <th scope="col">Status</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($admins as $admin)
                @php
                $adminActive = strtolower((string) $admin->status) === 'active';
                $isMe = $admin->user_id === auth()->id();
                @endphp
                <tr>
                    <td>USER-{{ $admin->user_id }}</td>
                    <td>
                        {{ $admin->username }}
                        @if ($isMe)
                        <span class="badge text-bg-secondary ms-1">You</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge text-bg-{{ $adminActive ? 'success' : 'danger' }}">
                            {{ $adminActive ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="text-nowrap">

                        
                        <button type="button"
                            class="btn btn-sm btn-outline-success reset-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#resetPassword"
                            data-user-id="{{ $admin->user_id }}"
                            data-username="{{ $admin->username }}"
                            title="Reset password" aria-label="Reset password">
                            <i class="bi bi-key"></i>
                        </button>

                        
                        @unless ($isMe)
                        <button type="button"
                            class="btn btn-sm toggle-btn {{ $adminActive ? 'btn-outline-danger' : 'btn-outline-info' }}"
                            data-bs-toggle="modal"
                            data-bs-target="#toggleAdmin"
                            data-user-id="{{ $admin->user_id }}"
                            data-username="{{ $admin->username }}"
                            data-active="{{ $adminActive ? '1' : '0' }}"
                            title="{{ $adminActive ? 'Deactivate' : 'Activate' }}"
                            aria-label="{{ $adminActive ? 'Deactivate admin' : 'Activate admin' }}">
                            <i class="bi {{ $adminActive ? 'bi-person-dash' : 'bi-person-check' }}"></i>
                        </button>
                        @endunless

                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="table-empty">
                        <i class="bi bi-inbox"></i>
                        No admins found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endif




@if ($popupSuccess || $popupError)
<div class="modal fade" id="resultModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content text-center p-3">
            <div class="modal-body">
                @if ($popupSuccess)
                <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                <h5 class="fw-bold mt-2">Success</h5>
                <p class="mb-0">{{ $popupSuccess }}</p>
                @else
                <i class="bi bi-exclamation-circle-fill text-danger" style="font-size: 4rem;"></i>
                <h5 class="fw-bold mt-2">Unable to continue</h5>
                <p class="mb-0">{{ $popupError }}</p>
                @endif
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button"
                    class="btn {{ $popupSuccess ? 'btn-info text-white' : 'btn-danger' }} px-4"
                    data-bs-dismiss="modal">
                    OK
                </button>
            </div>
        </div>
    </div>
</div>
@endif




<div class="modal fade" id="addMember" tabindex="-1" aria-hidden="true"
    data-show-on-load="{{ $errors->any() && ! $editFailed && ! $addAdminFailed && ! $resetFailed ? '1' : '0' }}">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('members.store') }}" class="modal-content p-3">
            @csrf

            <div class="modal-header border-0">
                <div>
                    <h5 class="modal-title fw-semibold">Register a Member</h5>
                    <small class="text-muted">Enter the member information below.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                @if ($errors->any() && ! $editFailed && ! $addAdminFailed && ! $resetFailed)
                <div class="alert alert-danger">
                    <strong><i class="bi bi-exclamation-circle me-1"></i> Please check the following:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div class="alert alert-info py-2">
                    <i class="bi bi-info-circle me-1"></i>
                    The member code is generated automatically.
                </div>

                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" id="full_name" name="full_name"
                        value="{{ $editFailed ? '' : old('full_name') }}"
                        class="form-control @error('full_name') is-invalid @enderror"
                        maxlength="255" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" id="email" name="email"
                        value="{{ $editFailed ? '' : old('email') }}"
                        class="form-control @error('email') is-invalid @enderror"
                        maxlength="255" required>
                </div>

                <div class="mb-0">
                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                    <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                        <option value="active" @selected(($editFailed ? 'active' : old('status', 'active' ))==='active' )>Active</option>
                        <option value="inactive" @selected(! $editFailed && old('status')==='inactive' )>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-info text-white">
                    <i class="bi bi-plus-circle me-1"></i> Register
                </button>
            </div>
        </form>
    </div>
</div>




<div class="modal fade" id="editMember" tabindex="-1" aria-hidden="true"
    data-show-on-load="{{ $editFailed ? '1' : '0' }}">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="editMemberForm"
            action="{{ $editFailed && old('edit_member_id') ? route('members.update', old('edit_member_id')) : '' }}"
            class="modal-content p-3">
            @csrf
            @method('PUT')

            <input type="hidden" name="edit_member_id" id="edit_member_id"
                value="{{ $editFailed ? old('edit_member_id') : '' }}">

            <div class="modal-header border-0">
                <div>
                    <h5 class="modal-title fw-semibold">Edit Member</h5>
                    <small class="text-muted">Update the member information below.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                @if ($editFailed)
                <div class="alert alert-danger" id="editErrors">
                    <strong><i class="bi bi-exclamation-circle me-1"></i> Please check the following:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->editMember->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                {{-- Display only: no name attribute, so it is never submitted --}}
                <div class="mb-3">
                    <label for="edit_member_code" class="form-label">Member Code</label>
                    <input type="text" id="edit_member_code" class="form-control" readonly disabled>
                </div>

                <div class="mb-3">
                    <label for="edit_full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" id="edit_full_name" name="full_name"
                        value="{{ $editFailed ? old('full_name') : '' }}"
                        class="form-control @if($errors->editMember->has('full_name')) is-invalid @endif"
                        maxlength="255" required>
                </div>

                <div class="mb-3">
                    <label for="edit_email" class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" id="edit_email" name="email"
                        value="{{ $editFailed ? old('email') : '' }}"
                        class="form-control @if($errors->editMember->has('email')) is-invalid @endif"
                        maxlength="255" required>
                </div>

                <div class="mb-0">
                    <label for="edit_status" class="form-label">Status <span class="text-danger">*</span></label>
                    <select id="edit_status" name="status"
                        class="form-select @if($errors->editMember->has('status')) is-invalid @endif" required>
                        <option value="active" @selected($editFailed && old('status')==='active' )>Active</option>
                        <option value="inactive" @selected($editFailed && old('status')==='inactive' )>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-info text-white">
                    <i class="bi bi-check-circle me-1"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>




<div class="modal fade" id="deleteMember" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-semibold">Delete Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Are you sure you want to delete this member?</p>
                <p class="fw-semibold mb-3" id="deleteMemberName"></p>
                <div class="alert alert-danger mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    This action cannot be undone. Members with borrowing records cannot be deleted.
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteMemberForm" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i> Delete Member
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@if ($isAdmin)



<div class="modal fade" id="addAdmin" tabindex="-1" aria-hidden="true"
    data-show-on-load="{{ $addAdminFailed ? '1' : '0' }}">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('members.store') }}" class="modal-content p-3">
            @csrf
            <input type="hidden" name="admin_action" value="create">

            <div class="modal-header border-0">
                <div>
                    <h5 class="modal-title fw-semibold">Add New Admin</h5>
                    <small class="text-muted">Create a login for another administrator.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                @if ($addAdminFailed)
                <div class="alert alert-danger">
                    <strong><i class="bi bi-exclamation-circle me-1"></i> Please check the following:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->addAdmin->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div class="mb-3">
                    <label for="new_username" class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" id="new_username" name="username"
                        value="{{ $addAdminFailed ? old('username') : '' }}"
                        class="form-control @if($errors->addAdmin->has('username')) is-invalid @endif"
                        placeholder="Letters, numbers, dashes, underscores"
                        minlength="4" maxlength="50" required autocomplete="off">
                </div>

                <div class="mb-3">
                    <label for="new_password" class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" id="new_password" name="password"
                        class="form-control @if($errors->addAdmin->has('password')) is-invalid @endif"
                        minlength="8" maxlength="100" required autocomplete="new-password">
                    <small class="text-muted">At least 8 characters.</small>
                </div>

                <div class="mb-0">
                    <label for="new_password_confirmation" class="form-label">Confirm password <span class="text-danger">*</span></label>
                    <input type="password" id="new_password_confirmation" name="password_confirmation"
                        class="form-control" minlength="8" maxlength="100" required autocomplete="new-password">
                </div>
            </div>

            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-info text-white">
                    <i class="bi bi-person-plus me-1"></i> Create Admin
                </button>
            </div>
        </form>
    </div>
</div>




<div class="modal fade" id="toggleAdmin" tabindex="-1" aria-labelledby="toggleAdminLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-semibold" id="toggleAdminLabel">Deactivate Admin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2" id="toggleAdminQuestion">Are you sure you want to deactivate this admin?</p>
                <p class="fw-semibold mb-3" id="toggleAdminName"></p>
                <div class="alert alert-danger mb-0" id="toggleAdminNote">
                    <i class="bi bi-info-circle me-1"></i>
                    <span id="toggleAdminNoteText">This admin will no longer be able to log in. You can activate the account again at any time.</span>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="toggleAdminForm" method="POST" action="{{ route('members.store') }}">
                    @csrf
                    <input type="hidden" name="admin_action" value="toggle">
                    <input type="hidden" name="admin_user_id" id="toggle_user_id" value="">
                    <button type="submit" class="btn btn-danger" id="toggleAdminSubmit">
                        <i class="bi bi-person-dash me-1" id="toggleAdminIcon"></i>
                        <span id="toggleAdminSubmitText">Deactivate</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>




<div class="modal fade" id="resetPassword" tabindex="-1" aria-hidden="true"
    data-show-on-load="{{ $resetFailed ? '1' : '0' }}">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('members.store') }}" class="modal-content p-3">
            @csrf
            <input type="hidden" name="admin_action" value="reset">
            <input type="hidden" name="admin_user_id" id="reset_user_id"
                value="{{ $resetFailed ? old('admin_user_id') : '' }}">

            <div class="modal-header border-0">
                <div>
                    <h5 class="modal-title fw-semibold">Reset Password</h5>
                    <small class="text-muted">New password for <strong id="resetUsername">{{ $resetAdmin->username ?? '' }}</strong></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                @if ($resetFailed)
                <div class="alert alert-danger" id="resetErrors">
                    <strong><i class="bi bi-exclamation-circle me-1"></i> Please check the following:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->resetPassword->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div class="mb-3">
                    <label for="reset_password" class="form-label">New password <span class="text-danger">*</span></label>
                    <input type="password" id="reset_password" name="password" class="form-control"
                        minlength="8" maxlength="100" required autocomplete="new-password">
                </div>

                <div class="mb-0">
                    <label for="reset_password_confirmation" class="form-label">Confirm new password <span class="text-danger">*</span></label>
                    <input type="password" id="reset_password_confirmation" name="password_confirmation"
                        class="form-control" minlength="8" maxlength="100" required autocomplete="new-password">
                </div>
            </div>

            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-key me-1"></i> Reset Password
                </button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script src="{{ asset('member.js') }}"></script>
@endpush