<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Add New Resident – Barangay Doyong EDRMS</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet"/>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}"/>
    
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <style>
        body { font-family: 'Public Sans', sans-serif; }
    </style>
    @include('components.ui-assets')
</head>
<body class="bg-[#f6f7f8] font-[Public_Sans] text-slate-800">

<div class="flex h-screen overflow-hidden">

    {{-- SIDEBAR --}}
    @include('admin.partials.sidebar')

    {{-- MAIN CONTENT --}}
    <main class="flex-1 flex flex-col overflow-hidden">
        
        {{-- Header --}}
        <header class="h-16 bg-white border-b border-slate-200 px-8 flex items-center justify-between z-10 flex-shrink-0">
@include('components.navigation-toggle')
            <div>
                <h2 class="text-xl font-bold text-slate-800">Add New Resident</h2>
                <p class="text-xs text-slate-500">Create a new resident record in the system</p>
            </div>
            <a href="{{ route('admin.residents.index') }}" class="text-slate-500 hover:text-slate-700">
                <span class="material-icons-round">close</span>
            </a>
        </header>

        {{-- Content --}}
        <div class="flex-1 overflow-y-auto p-8">
            <div class="max-w-2xl bg-white rounded-xl border border-slate-200 shadow-sm p-8">
                
                <form method="POST" action="{{ route('admin.residents.store') }}" class="space-y-6">
                    @csrf

                    {{-- Name Fields Row --}}
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">First Name *</label>
                            <input type="text" name="first_name" value="{{ old('first_name') }}" required
                                   class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#15803d] focus:border-transparent outline-none {{ $errors->has('first_name') ? 'border-red-500' : '' }}"
                                   placeholder="Juan"/>
                            @error('first_name')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Middle Name</label>
                            <input type="text" name="middle_name" value="{{ old('middle_name') }}"
                                   class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#15803d] focus:border-transparent outline-none"
                                   placeholder="Optional"/>
                            @error('middle_name')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Last Name *</label>
                            <input type="text" name="last_name" value="{{ old('last_name') }}" required
                                   class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#15803d] focus:border-transparent outline-none {{ $errors->has('last_name') ? 'border-red-500' : '' }}"
                                   placeholder="Dela Cruz"/>
                            @error('last_name')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Suffix --}}
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Suffix</label>
                        <input type="text" name="suffix" value="{{ old('suffix') }}"
                               class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#15803d] focus:border-transparent outline-none"
                               placeholder="Jr., Sr., III, etc."/>
                        @error('suffix')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Address --}}
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Address *</label>
                        <input type="text" name="address" value="{{ old('address') }}" required
                               class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#15803d] focus:border-transparent outline-none {{ $errors->has('address') ? 'border-red-500' : '' }}"
                               placeholder="Street, Barangay, Municipality"/>
                        @error('address')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Contact & Birthdate Row --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Contact Number</label>
                            <input type="text" name="contact_number" value="{{ old('contact_number') }}"
                                   class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#15803d] focus:border-transparent outline-none"
                                   placeholder="09XX-XXX-XXXX"/>
                            @error('contact_number')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Birthdate</label>
                            <input type="date" name="birthdate" value="{{ old('birthdate') }}"
                                   class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#15803d] focus:border-transparent outline-none"/>
                            @error('birthdate')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Civil Status & Zone Row --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Civil Status</label>
                            <select name="civil_status" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#15803d] focus:border-transparent outline-none">
                                <option value="">Select Status</option>
                                <option value="single" {{ old('civil_status') === 'single' ? 'selected' : '' }}>Single</option>
                                <option value="married" {{ old('civil_status') === 'married' ? 'selected' : '' }}>Married</option>
                                <option value="widowed" {{ old('civil_status') === 'widowed' ? 'selected' : '' }}>Widowed</option>
                                <option value="separated" {{ old('civil_status') === 'separated' ? 'selected' : '' }}>Separated</option>
                            </select>
                            @error('civil_status')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Zone</label>
                            <select name="zone" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#15803d] focus:border-transparent outline-none">
                                <option value="">Select Zone</option>
                                <option value="zone1" {{ old('zone') === 'zone1' ? 'selected' : '' }}>Zone 1</option>
                                <option value="zone2" {{ old('zone') === 'zone2' ? 'selected' : '' }}>Zone 2</option>
                                <option value="zone3" {{ old('zone') === 'zone3' ? 'selected' : '' }}>Zone 3</option>
                                <option value="zone4" {{ old('zone') === 'zone4' ? 'selected' : '' }}>Zone 4</option>
                            </select>
                            @error('zone')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Status --}}
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Status *</label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="status" value="active" {{ old('status', 'active') === 'active' ? 'checked' : '' }} required/>
                                <span class="text-sm text-slate-700">Active</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="status" value="inactive" {{ old('status') === 'inactive' ? 'checked' : '' }}/>
                                <span class="text-sm text-slate-700">Inactive</span>
                            </label>
                        </div>
                        @error('status')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Form Actions --}}
                    <div class="flex gap-3 pt-4 border-t border-slate-200">
                        <a href="{{ route('admin.residents.index') }}" 
                           class="px-6 py-2 border border-slate-300 rounded-lg text-slate-700 font-medium hover:bg-slate-50 transition-colors">
                            Cancel
                        </a>
                        <button type="submit" 
                                class="px-6 py-2 bg-[#15803d] text-white rounded-lg font-medium hover:bg-green-600 transition-colors">
                            <span class="material-icons-round inline mr-2" style="vertical-align: middle; font-size: 20px;">save</span>
                            Add Resident
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

</body>
</html>
