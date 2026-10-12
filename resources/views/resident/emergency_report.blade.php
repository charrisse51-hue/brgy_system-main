@extends('layouts.app')

@section('title', 'Submit Incident Report - Barangay Doyong')

{{-- ── Styles ── --}}
@push('styles')
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="{{ asset('css/resident/resiemergency.css') }}"/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

{{-- ── Content ── --}}
@section('content')

    {{-- ════════════════════════════
         NAVBAR
         ════════════════════════════ --}}
    <nav class="navbar">
        <div class="navbar-inner">

            {{-- Brand --}}
            <a href="{{ route('resident.dashboard') }}" class="navbar-brand">
                <div class="navbar-brand-icon">
                    <span class="material-symbols-outlined">emergency</span>
                </div>
                <div>
                    <div class="navbar-brand-title">Barangay Doyong</div>
                    <div class="navbar-brand-sub">Emergency &amp; Disaster Management</div>
                </div>
            </a>

            {{-- Links --}}
            <div class="navbar-links">
                <a href="{{ route('resident.dashboard') }}"
                   class="{{ request()->routeIs('resident.dashboard') ? 'active' : '' }}">
                    Dashboard
                </a>
                <a href="{{ route('resident.emergency.create') }}"
                   class="{{ request()->routeIs('resident.emergency.*') ? 'active' : '' }}">
                    Report Incident
                </a>

                {{-- Avatar --}}
                <div class="navbar-avatar">
                    @if(Auth::user()->profile_photo ?? null)
                        <img src="{{ Storage::url(Auth::user()->profile_photo) }}"
                             alt="{{ Auth::user()->name }}"/>
                    @else
                        <span class="material-symbols-outlined">person</span>
                    @endif
                </div>
            </div>

        </div>
    </nav>

    {{-- ════════════════════════════
         MAIN
         ════════════════════════════ --}}
    <main class="page-main">
        <div class="page-container">

            {{-- Back button --}}
            <a href="{{ route('resident.dashboard') }}" class="btn-back">
                <span class="material-symbols-outlined">arrow_back</span>
                Back to Dashboard
            </a>

            {{-- ── Form Card ── --}}
            <div class="form-card">

                {{-- Progress bar (full = ready to submit) --}}
                <div class="form-card-progress">
                    <div class="form-card-progress-fill" id="progress_fill"></div>
                </div>

                <div class="form-card-body">

                    {{-- Card Header --}}
                    <header class="form-card-header">
                        <h2 class="form-card-title">Submit Incident Report</h2>
                        <p class="form-card-sub">Quickly report an incident to our response team.</p>
                    </header>

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

                    {{-- ── Form ── --}}
                    <form
                        id="report_form"
                        method="POST"
                        action="{{ route('resident.emergency.store') }}"
                        enctype="multipart/form-data"
                        novalidate
                    >
                        @csrf

                        <div class="form-body">

                            {{-- Type of Incident --}}
                            <div class="form-group">
                                <label class="form-label" for="incident_type">
                                    Type of Incident <span class="required-star">*</span>
                                </label>
                                <div class="select-wrapper">
                                    <select
                                        id="incident_type"
                                        name="incident_type"
                                        class="form-select {{ $errors->has('incident_type') ? 'is-invalid' : '' }}"
                                        required
                                    >
                                        <option value="" disabled {{ old('incident_type') ? '' : 'selected' }}>
                                            Select an incident type
                                        </option>
                                        <option value="fire" {{ old('incident_type') == 'fire' ? 'selected' : '' }}>Fire Incident</option>
                                        <option value="accident" {{ old('incident_type') == 'accident' ? 'selected' : '' }}>Road / Medical Accident</option>
                                        <option value="theft" {{ old('incident_type') == 'theft' ? 'selected' : '' }}>Theft / Crime</option>
                                        <option value="flood" {{ old('incident_type') == 'flood' ? 'selected' : '' }}>Flooding / Natural Disaster</option>
                                        <option value="others" {{ old('incident_type') == 'others' ? 'selected' : '' }}>Others</option>
                                    </select>
                                    <span class="select-arrow material-symbols-outlined">expand_more</span>
                                </div>
                                @error('incident_type')
                                    <div class="field-error">
                                        <span class="material-symbols-outlined">error_outline</span>
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            {{-- Description (optional but helpful) --}}
                            <div class="form-group">
                                <label class="form-label" for="description">
                                    Description
                                </label>
                                <textarea
                                    id="description"
                                    name="description"
                                    class="form-textarea {{ $errors->has('description') ? 'is-invalid' : '' }}"
                                    placeholder="Briefly describe what happened..."
                                >{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="field-error">
                                        <span class="material-symbols-outlined">error_outline</span>
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            {{-- Location --}}
                            <div class="form-group">
                                <label class="form-label">
                                    Incident Location <span class="required-star">*</span>
                                </label>
                                <p class="form-help">Your current location is shown on the map. Drag the pin, click a spot, or press the button to set the incident location.</p>
                                <div class="map-wrapper">
                                    <div id="map" style="height: 400px; width: 100%;"></div>
                                    <button id="locate_current_position" type="button" class="map-action-button" title="Pin current location">
                                        <span class="material-symbols-outlined">my_location</span>
                                    </button>
                                </div>
                                <input type="hidden" id="latitude" name="latitude" required>
                                <input type="hidden" id="longitude" name="longitude" required>
                                @error('latitude')
                                    <div class="field-error">
                                        <span class="material-symbols-outlined">error_outline</span>
                                        {{ $message }}
                                    </div>
                                @enderror
                                @error('longitude')
                                    <div class="field-error">
                                        <span class="material-symbols-outlined">error_outline</span>
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            {{-- Photo Evidence --}}
                            <div class="form-group">
                                <label class="form-label">
                                    Attach Photo Evidence
                                </label>

                                <div id="upload_zone" class="upload-zone {{ $errors->has('photos') ? 'is-invalid' : '' }}">
                                    <div class="upload-icon-wrap">
                                        <span class="material-symbols-outlined">cloud_upload</span>
                                    </div>
                                    <p class="upload-title">Click to upload or drag and drop</p>
                                    <p class="upload-sub">PNG, JPG or JPEG (max. 5 MB per file)</p>

                                    <input
                                        id="photo_evidence"
                                        name="photos[]"
                                        type="file"
                                        class="upload-input"
                                        accept="image/png,image/jpeg,image/jpg"
                                        multiple
                                    />
                                </div>

                                {{-- JS-rendered thumbnails appear here --}}
                                <div id="upload_preview" class="upload-preview"></div>

                                @error('photos')
                                    <div class="field-error">
                                        <span class="material-symbols-outlined">error_outline</span>
                                        {{ $message }}
                                    </div>
                                @enderror
                                @error('photos.*')
                                    <div class="field-error">
                                        <span class="material-symbols-outlined">error_outline</span>
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            {{-- Submit --}}
                            <div class="form-submit-section">
                                <button type="submit" class="btn-submit">
                                    <span>Submit Incident Report</span>
                                    <span class="material-symbols-outlined">send</span>
                                </button>

                                <p class="emergency-note">
                                    <span class="material-symbols-outlined">info</span>
                                    If this is a life-threatening emergency, please call
                                    <strong>911</strong> or your local emergency hotline immediately.
                                </p>
                            </div>

                        </div>{{-- /form-body --}}
                    </form>

                </div>{{-- /form-card-body --}}
            </div>{{-- /form-card --}}

            <footer class="page-footer">
                <p>© {{ date('Y') }} Barangay Doyong Management System. All records are confidential.</p>
            </footer>

        </div>
    </main>

@endsection

{{-- ── Scripts ── --}}
@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="{{ asset('js/resident/resiemergency.js') }}" defer></script>
@endpush