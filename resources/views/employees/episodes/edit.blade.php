@extends('layouts.hrms')

@section('content')
    <h1>Edit employment episode</h1>
    <p class="muted">{{ $employee->display_name }} · {{ $employee->employee_code }}</p>
    <p class="note">Overlap with another episode is not checked. Date order is not checked. Inactive has no pay rule on this screen. {{ \App\Employees\EmploymentEpisodeRegistry::CLOSED_EPISODE_REOPEN_MESSAGE }}</p>
    @include('employees.partials.errors')
    @include('employees.episodes.form', [
        'action' => route('employees.episodes.update', [$employee, $episode]),
        'method' => 'PUT',
        'episode' => $episode,
    ])
@endsection
