@extends('layouts.app')

@section('title', 'Personal Profile - Barangay Doyong')

{{-- ── Styles ── --}}
@push('styles')
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    {{-- use resident profile stylesheet for the layout and form styling --}}
    <link rel="stylesheet" href="{{ asset('css/resident/resident_profile.css') }}"/>
@endpush

{{--
    data-show-edit tells JS to auto-open the edit form if Laravel
    redirected back after a failed validation.
--}}
@section('content')
<div class="layout-wrapper" data-show-edit="{{ $errors->any() ? 'true' : 'false' }}">

    {{-- ════════════════════════════
         SIDEBAR
         ════════════════════════════ --}}
    <aside class="sidebar">

        <div class="sidebar-brand">
            <div class="sidebar-brand-icon">
                <span class="material-symbols-outlined">gpp_maybe</span>
            </div>
            <div>
                <div class="sidebar-brand-title">Brgy. Doyong</div>
                <div class="sidebar-brand-sub">Resident Portal</div>
            </div>
        </div>

        <div class="sidebar-emergency-wrap">
            <a href="{{ route('resident.emergency.create') }}" class="btn-emergency">
                <span class="material-symbols-outlined">emergency_share</span>
                <span>REPORT EMERGENCY</span>
            </a>
        </div>

        <nav class="sidebar-nav">
            <a href="{{ route('resident.dashboard') }}"
               class="nav-link {{ request()->routeIs('resident.dashboard') ? 'active' : '' }}">
                <span class="material-symbols-outlined">dashboard</span>
                <span>Dashboard</span>
            </a>
            <a href="{{ route('resident.profile') }}"
               class="nav-link {{ request()->routeIs('resident.profile') ? 'active' : '' }}">
                <span class="material-symbols-outlined">person</span>
                <span>Personal Profile</span>
            </a>
            <a href="{{ route('resident.alerts') }}"
               class="nav-link {{ request()->routeIs('resident.alerts') ? 'active' : '' }}">
                <span class="material-symbols-outlined">campaign</span>
                <span>Community Alerts</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="hotline-card">
                <div class="hotline-label">Emergency Hotline</div>
                <div class="hotline-number">911-DOYONG</div>
            </div>
            <form id="signout_form" method="POST" action="{{ route('logout') }}" style="display:none">
                @csrf
            </form>
            <button id="btn_signout" type="button" class="btn-signout">
                <span class="material-symbols-outlined">logout</span>
                <span>Sign Out</span>
            </button>
        </div>

    </aside>

    {{-- ════════════════════════════
         MAIN
         ════════════════════════════ --}}
    <main class="main-content">

        {{-- ── Sticky Header ── --}}
        <header class="page-header">
            @include('components.navigation-toggle')
            <div class="breadcrumb">
                <span class="breadcrumb-item">Resident</span>
                <span class="breadcrumb-sep material-symbols-outlined">chevron_right</span>
                <span class="breadcrumb-item current" id="breadcrumb_label">Personal Profile</span>
            </div>
            <div class="header-right">
                <button class="btn-notif" id="btn_notif" type="button">
                    <span class="material-symbols-outlined">notifications</span>
                    @if(($unreadNotifications ?? 0) > 0)
                        <span class="notif-dot"></span>
                    @endif
                </button>
                <div class="header-divider"></div>
                <div class="header-user">
                    <div class="user-info-text">
                        <div class="user-name">{{ Auth::user()->name }}</div>
                        <div class="user-sub">Purok {{ $resident->purok ?? '—' }}, Resident</div>
                    </div>
                    <div class="user-avatar-wrap">
                        @if(Auth::user()->profile_photo ?? null)
                            <img src="{{ Storage::url(Auth::user()->profile_photo) }}" alt="{{ Auth::user()->name }}"/>
                        @else
                            {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                        @endif
                    </div>
                </div>
            </div>
        </header>

        {{-- ── Page Body ── --}}
        <div class="page-inner" id="page_body">

            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="alert alert-success">
                    <span class="material-symbols-outlined">check_circle</span>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-error">
                    <span class="material-symbols-outlined">error</span>
                    {{ session('error') }}
                </div>
            @endif

            {{-- ══════════════════════════════════════════════
                 SECTION 1 — PROFILE VIEW
                 Shown by default. Hidden when Edit is clicked.
                 ══════════════════════════════════════════════ --}}
            <div id="profile_section" class="profile-section">

                {{-- Name + Edit button --}}
                <div class="profile-header-row">
                    <div class="profile-name-block">
                        <h2 class="profile-full-name">
                            {{ $resident->first_name }}
                            {{ $resident->middle_name ? $resident->middle_name . '.' : '' }}
                            {{ $resident->last_name }}
                            {{ $resident->suffix ?? '' }}
                        </h2>
                        <p class="profile-resident-id">Resident ID: #DOY-{{ now()->year }}-{{ str_pad($resident->id, 4, '0', STR_PAD_LEFT) }}</p>
                        <span class="verified-badge">
                            <span class="material-symbols-outlined">verified</span>
                            Verified Resident
                        </span>
                    </div>
                    <button id="btn_edit_profile" type="button" class="btn-edit-profile">
                        <span class="material-symbols-outlined">edit</span>
                        Edit Profile
                    </button>
                </div>

                {{-- Basic Information --}}
                <div class="info-card">
                    <div class="info-card-header">
                        <span class="material-symbols-outlined">person</span>
                        <span class="info-card-header-title">Basic Information</span>
                    </div>
                    <div class="info-card-body">
                        <div class="info-grid">
                            <div>
                                <div class="info-field-label">Full Name</div>
                                <div class="info-field-value">
                                    {{ $resident->first_name }}
                                    {{ $resident->middle_name ?? '' }}
                                    {{ $resident->last_name }}
                                    {{ $resident->suffix ?? '' }}
                                </div>
                            </div>
                            <div>
                                <div class="info-field-label">Gender</div>
                                <div class="info-field-value">{{ ucfirst($resident->gender ?? '—') }}</div>
                            </div>
                            <div>
                                <div class="info-field-label">Birth Date</div>
                                <div class="info-field-value">
                                    {{ $resident->birthdate ? \Carbon\Carbon::parse($resident->birthdate)->format('F j, Y') : '—' }}
                                </div>
                            </div>
                            <div>
                                <div class="info-field-label">Civil Status</div>
                                <div class="info-field-value">{{ ucfirst($resident->civil_status ?? '—') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Contact Details --}}
                <div class="info-card">
                    <div class="info-card-header">
                        <span class="material-symbols-outlined">contact_phone</span>
                        <span class="info-card-header-title">Contact Details</span>
                    </div>
                    <div class="info-card-body">
                        <div class="info-grid-2">
                            <div>
                                <div class="info-field-label">Phone Number</div>
                                <div class="contact-row">
                                    <span class="contact-icon"><span class="material-symbols-outlined">phone</span></span>
                                    <span class="info-field-value">+63 {{ $resident->phone ?? '—' }}</span>
                                </div>
                            </div>
                            <div>
                                <div class="info-field-label">Email Address</div>
                                <div class="contact-row">
                                    <span class="contact-icon"><span class="material-symbols-outlined">mail</span></span>
                                    <span class="info-field-value">{{ $resident->email ?? '—' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Address Information --}}
                <div class="info-card">
                    <div class="info-card-header">
                        <span class="material-symbols-outlined">location_on</span>
                        <span class="info-card-header-title">Address Information</span>
                    </div>
                    <div class="info-card-body">
                        <div class="info-grid-3">
                            <div>
                                <div class="info-field-label">Purok</div>
                                <div class="info-field-value">
                                    <span class="address-purok">
                                        <span class="material-symbols-outlined">place</span>
                                        Purok {{ $resident->purok ?? '—' }}
                                        @if($resident->purok_name ?? null) ({{ $resident->purok_name }}) @endif
                                    </span>
                                </div>
                            </div>
                            <div>
                                <div class="info-field-label">Street</div>
                                <div class="info-field-value">{{ $resident->street ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="info-field-label">House Number</div>
                                <div class="info-field-value">{{ $resident->house_number ?? '—' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="privacy-note">
                    <span class="material-symbols-outlined">info</span>
                    This information is protected under the Data Privacy Act and is used only for official barangay records and response purposes.
                </div>

            </div>{{-- /profile_section --}}

            {{-- ══════════════════════════════════════════════
                 SECTION 2 — EDIT PERSONAL INFORMATION
                 Hidden by default. Shown when Edit is clicked.
                 JS auto-opens this if Laravel returns errors.
                 ══════════════════════════════════════════════ --}}
            <div id="edit_section" class="edit-section hidden">

                {{-- Edit header --}}
                <div class="edit-header-row">
                    <div>
                        <h2 class="edit-title">Edit Personal Information</h2>
                        <p class="edit-sub">Update your account details and address information.</p>
                    </div>
                    <div class="edit-header-actions">
                        <button id="btn_cancel_top" type="button" class="btn-cancel">Cancel</button>
                        <button type="submit" form="edit_profile_form" class="btn-save">
                            <span class="material-symbols-outlined">save</span>
                            Save Changes
                        </button>
                    </div>
                </div>

                <form
                    id="edit_profile_form"
                    method="POST"
                    action="{{ route('resident.profile.update') }}"
                    novalidate
                >
                    @csrf
                    @method('PUT')

                    {{-- Basic Information --}}
                    <div class="form-section-card">
                        <div class="form-section-header">
                            <span class="material-symbols-outlined">person</span>
                            <span class="form-section-title">Basic Information</span>
                        </div>
                        <div class="form-section-body">
                            <div class="form-grid-4">

                                {{-- First Name --}}
                                <div class="form-group">
                                    <label class="form-label" for="edit_first_name">
                                        First Name <span class="required-star">*</span>
                                    </label>
                                    <input id="edit_first_name" name="first_name" type="text"
                                           class="form-input {{ $errors->has('first_name') ? 'is-invalid' : '' }}"
                                           value="{{ old('first_name', $resident->first_name) }}"
                                           placeholder="Juan" required/>
                                    @error('first_name')
                                        <div class="field-error"><span class="material-symbols-outlined">error_outline</span>{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Middle Name --}}
                                <div class="form-group">
                                    <label class="form-label" for="edit_middle_name">Middle Initial</label>
                                    <input id="edit_middle_name" name="middle_name" type="text"
                                           class="form-input {{ $errors->has('middle_name') ? 'is-invalid' : '' }}"
                                           value="{{ old('middle_name', $resident->middle_name) }}"
                                           placeholder="M."/>
                                    @error('middle_name')
                                        <div class="field-error"><span class="material-symbols-outlined">error_outline</span>{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Last Name --}}
                                <div class="form-group">
                                    <label class="form-label" for="edit_last_name">
                                        Last Name <span class="required-star">*</span>
                                    </label>
                                    <input id="edit_last_name" name="last_name" type="text"
                                           class="form-input {{ $errors->has('last_name') ? 'is-invalid' : '' }}"
                                           value="{{ old('last_name', $resident->last_name) }}"
                                           placeholder="Dela Cruz" required/>
                                    @error('last_name')
                                        <div class="field-error"><span class="material-symbols-outlined">error_outline</span>{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Suffix --}}
                                <div class="form-group">
                                    <label class="form-label" for="edit_suffix">Suffix</label>
                                    <input id="edit_suffix" name="suffix" type="text"
                                           class="form-input {{ $errors->has('suffix') ? 'is-invalid' : '' }}"
                                           value="{{ old('suffix', $resident->suffix) }}"
                                           placeholder="e.g. Jr, Sr, III"/>
                                    @error('suffix')
                                        <div class="field-error"><span class="material-symbols-outlined">error_outline</span>{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Gender --}}
                                <div class="form-group">
                                    <label class="form-label" for="edit_gender">
                                        Gender <span class="required-star">*</span>
                                    </label>
                                    <div class="select-wrap">
                                        <select id="edit_gender" name="gender"
                                                class="form-select {{ $errors->has('gender') ? 'is-invalid' : '' }}" required>
                                            <option value="" disabled {{ old('gender', $resident->gender) ? '' : 'selected' }}>Select</option>
                                            <option value="male"   {{ old('gender', $resident->gender) == 'male'   ? 'selected' : '' }}>Male</option>
                                            <option value="female" {{ old('gender', $resident->gender) == 'female' ? 'selected' : '' }}>Female</option>
                                            <option value="other"  {{ old('gender', $resident->gender) == 'other'  ? 'selected' : '' }}>Other</option>
                                        </select>
                                        <span class="select-arrow material-symbols-outlined">expand_more</span>
                                    </div>
                                    @error('gender')
                                        <div class="field-error"><span class="material-symbols-outlined">error_outline</span>{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Birthdate --}}
                                <div class="form-group">
                                    <label class="form-label" for="edit_birthdate">
                                        Birth Date <span class="required-star">*</span>
                                    </label>
                                    <input id="edit_birthdate" name="birthdate" type="date"
                                           class="form-input {{ $errors->has('birthdate') ? 'is-invalid' : '' }}"
                                           value="{{ old('birthdate', $resident->birthdate ? \Carbon\Carbon::parse($resident->birthdate)->format('Y-m-d') : '') }}"
                                           max="{{ date('Y-m-d') }}" required/>
                                    @error('birthdate')
                                        <div class="field-error"><span class="material-symbols-outlined">error_outline</span>{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Civil Status --}}
                                <div class="form-group">
                                    <label class="form-label" for="edit_civil_status">
                                        Civil Status <span class="required-star">*</span>
                                    </label>
                                    <div class="select-wrap">
                                        <select id="edit_civil_status" name="civil_status"
                                                class="form-select {{ $errors->has('civil_status') ? 'is-invalid' : '' }}" required>
                                            <option value="" disabled {{ old('civil_status', $resident->civil_status) ? '' : 'selected' }}>Select</option>
                                            <option value="single"    {{ old('civil_status', $resident->civil_status) == 'single'    ? 'selected' : '' }}>Single</option>
                                            <option value="married"   {{ old('civil_status', $resident->civil_status) == 'married'   ? 'selected' : '' }}>Married</option>
                                            <option value="widowed"   {{ old('civil_status', $resident->civil_status) == 'widowed'   ? 'selected' : '' }}>Widowed</option>
                                            <option value="separated" {{ old('civil_status', $resident->civil_status) == 'separated' ? 'selected' : '' }}>Separated</option>
                                        </select>
                                        <span class="select-arrow material-symbols-outlined">expand_more</span>
                                    </div>
                                    @error('civil_status')
                                        <div class="field-error"><span class="material-symbols-outlined">error_outline</span>{{ $message }}</div>
                                    @enderror
                                </div>

                            </div>
                        </div>
                    </div>

                    {{-- Contact Information --}}
                    <div class="form-section-card">
                        <div class="form-section-header">
                            <span class="material-symbols-outlined">contact_phone</span>
                            <span class="form-section-title">Contact Information</span>
                        </div>
                        <div class="form-section-body">
                            <div class="form-grid-2">

                                {{-- Phone --}}
                                <div class="form-group">
                                    <label class="form-label" for="edit_phone">
                                        Phone Number <span class="required-star">*</span>
                                    </label>
                                    <input id="edit_phone" name="phone" type="tel"
                                           class="form-input {{ $errors->has('phone') ? 'is-invalid' : '' }}"
                                           value="{{ old('phone', $resident->phone) }}"
                                           placeholder="9123456789" required/>
                                    @error('phone')
                                        <div class="field-error"><span class="material-symbols-outlined">error_outline</span>{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Email --}}
                                <div class="form-group">
                                    <label class="form-label" for="edit_email">
                                        Email Address <span class="required-star">*</span>
                                    </label>
                                    <input id="edit_email" name="email" type="email"
                                           class="form-input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                                           value="{{ old('email', $resident->email) }}"
                                           placeholder="juan.delacruz@email.com" required/>
                                    @error('email')
                                        <div class="field-error"><span class="material-symbols-outlined">error_outline</span>{{ $message }}</div>
                                    @enderror
                                </div>

                            </div>
                        </div>
                    </div>

                    {{-- Address Information --}}
                    <div class="form-section-card">
                        <div class="form-section-header">
                            <span class="material-symbols-outlined">location_on</span>
                            <span class="form-section-title">Address Information</span>
                        </div>
                        <div class="form-section-body">
                            <div class="form-grid-3">

                                {{-- Purok --}}
                                <div class="form-group">
                                    <label class="form-label" for="edit_purok">
                                        Purok <span class="required-star">*</span>
                                    </label>
                                    <input id="edit_purok" name="purok" type="number" min="1" max="99"
                                           class="form-input {{ $errors->has('purok') ? 'is-invalid' : '' }}"
                                           value="{{ old('purok', $resident->purok) }}"
                                           placeholder="1" required/>
                                    @error('purok')
                                        <div class="field-error"><span class="material-symbols-outlined">error_outline</span>{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Street --}}
                                <div class="form-group">
                                    <label class="form-label" for="edit_street">
                                        Street <span class="required-star">*</span>
                                    </label>
                                    <input id="edit_street" name="street" type="text"
                                           class="form-input {{ $errors->has('street') ? 'is-invalid' : '' }}"
                                           value="{{ old('street', $resident->street) }}"
                                           placeholder="Main Street" required/>
                                    @error('street')
                                        <div class="field-error"><span class="material-symbols-outlined">error_outline</span>{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- House Number --}}
                                <div class="form-group">
                                    <label class="form-label" for="edit_house_number">
                                        House Number <span class="required-star">*</span>
                                    </label>
                                    <input id="edit_house_number" name="house_number" type="text"
                                           class="form-input {{ $errors->has('house_number') ? 'is-invalid' : '' }}"
                                           value="{{ old('house_number', $resident->house_number) }}"
                                           placeholder="123" required/>
                                    @error('house_number')
                                        <div class="field-error"><span class="material-symbols-outlined">error_outline</span>{{ $message }}</div>
                                    @enderror
                                </div>

                            </div>
                        </div>
                    </div>

                    {{-- Form Actions --}}
                    <div class="form-actions">
                        <button id="btn_cancel_bottom" type="button" class="btn-cancel">Cancel</button>
                        <button type="submit" class="btn-save">
                            <span class="material-symbols-outlined">save</span>
                            Save Changes
                        </button>
                    </div>

                </form>

            </div>{{-- /edit_section --}}

        </div>{{-- /page-inner --}}

        {{-- ── Footer ── --}}
        <footer class="page-footer">
            <p>© {{ date('Y') }} Barangay Doyong Emergency and Records Management System. All rights reserved.</p>
            <div class="footer-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">System Status</a>
            </div>
        </footer>

    </main>

</div>
@endsection

{{-- ── Scripts ── --}}
@push('scripts')
    @vite('resources/js/resident/resident.js')
    <script>
        // Profile edit toggle
        document.addEventListener('DOMContentLoaded', function() {
            const profileSection = document.getElementById('profile_section');
            const editSection = document.getElementById('edit_section');
            const btnEdit = document.getElementById('btn_edit_profile');
            const btnCancelTop = document.getElementById('btn_cancel_top');
            const btnCancelBottom = document.getElementById('btn_cancel_bottom');
            const breadcrumbLabel = document.getElementById('breadcrumb_label');

            // Auto-show edit if redirected with errors
            if (document.querySelector('.layout-wrapper').dataset.showEdit === 'true') {
                showEdit();
            }

            // Edit button
            btnEdit.addEventListener('click', showEdit);

            // Cancel buttons
            btnCancelTop.addEventListener('click', hideEdit);
            btnCancelBottom.addEventListener('click', hideEdit);

            function showEdit() {
                profileSection.classList.add('hidden');
                editSection.classList.remove('hidden');
                breadcrumbLabel.textContent = 'Edit Profile';
                window.scrollTo(0, 0);
            }

            function hideEdit() {
                editSection.classList.add('hidden');
                profileSection.classList.remove('hidden');
                breadcrumbLabel.textContent = 'Personal Profile';
                window.scrollTo(0, 0);
            }
        });
    </script>
@endpush
