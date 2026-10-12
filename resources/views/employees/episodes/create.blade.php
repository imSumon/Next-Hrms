@extends('layouts.hrms')

@section('content')
    <h1>Add employment episode</h1>
    <p class="muted">{{ $employee->display_name }} · {{ $employee->employee_code }}</p>
    <p class="row-actions">
        <a href="{{ route('employees.show', $employee) }}">Back to profile</a>
    </p>
    <p class="note">Overlap with another episode is not checked. Date order is not checked. Inactive has no pay rule on this screen. Adding a new episode is the approved rejoin path. Rejoining keeps the same employee identity and employee code, creates a new employment episode, and leaves the previous episode unchanged. A second Open episode is refused.</p>
    @include('employees.partials.errors')
    @include('employees.episodes.form', [
        'action' => route('employees.episodes.store', $employee),
        'method' => 'POST',
        'episode' => null,
    ])
@endsection
