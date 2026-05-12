{{--
    Reusable employee avatar component.

    Props:
      $employee  — Employee model instance
      $size      — Tailwind size classes, default 'w-10 h-10'
      $textSize  — Tailwind text class for initials, default 'text-sm'
--}}
@props(['employee', 'size' => 'w-10 h-10', 'textSize' => 'text-sm'])

@php
    $initials = strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name ?? '', 0, 1));
@endphp

@if($employee->profile_photo)
    <img src="{{ asset('storage/' . $employee->profile_photo) }}"
         alt="{{ $employee->full_name }}"
         class="{{ $size }} rounded-full object-cover object-center shrink-0 ring-2 ring-white dark:ring-gray-700" />
@else
    <div class="{{ $size }} rounded-full bg-gradient-to-br from-primary to-info text-white flex items-center justify-center font-bold shrink-0 {{ $textSize }}">
        {{ $initials }}
    </div>
@endif
