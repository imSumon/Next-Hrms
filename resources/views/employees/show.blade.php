@extends('layouts.hrms')

@section('content')
    <h1>{{ $employee->display_name }}</h1>
    <p class="muted">{{ $company->company_name }} · {{ $employee->employee_code }}</p>
    <p class="row-actions">
        @if ($canManageEmployees)
            <a class="button secondary" href="{{ route('employees.edit', $employee) }}">Edit name</a>
        @endif
        <a href="{{ route('employees.index') }}">Back to directory</a>
    </p>

    <section class="card">
        <h2>Identity</h2>
        <p>The employee code stays with this identity inside {{ $company->company_name }}. It is not reused after separation, and it is not an identity shared with another company.</p>
        <dl>
            <dt>Identity</dt>
            <dd>{{ $employee->id }}</dd>
            <dt>Recorded</dt>
            <dd>{{ $employee->created_at }}</dd>
            <dt>Updated</dt>
            <dd>{{ $employee->updated_at }}</dd>
        </dl>
        <p>Joining date and last working date are stored on an employment episode. This identity has no separate joining date, salary, or payable-day figure.</p>
    </section>

    <section class="card">
        <h2>Employment episodes</h2>
        <p class="note">Date overlap, date order, and the pay effect of inactive are not approved rules. This screen does not apply them. Rejoining keeps the same employee identity and employee code, creates a new employment episode, and leaves the previous episode unchanged. Add episode is that path. A second Open episode is refused.</p>
        @if ($canManageEmployees)
            <p><a class="button" href="{{ route('employees.episodes.create', $employee) }}">Add episode</a></p>
        @endif
        @if ($employee->episodes->isEmpty())
            <p class="empty">No employment episodes.</p>
        @else
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Joining date</th>
                            <th>Last working date</th>
                            <th>Status</th>
                            <th>Updated</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($employee->episodes as $episode)
                            <tr>
                                <td>{{ $episode->joining_date }}</td>
                                <td>{{ $episode->last_working_date ?: '—' }}</td>
                                <td>{{ $episode->episode_status }}</td>
                                <td>{{ $episode->updated_at }}</td>
                                <td>
                                    @if ($canManageEmployees)
                                        <a href="{{ route('employees.episodes.edit', [$employee, $episode]) }}">Edit</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="card">
        <h2>Organization and location</h2>
        <p>No assignment is stored for this employee. Branch, department, and location are separate foundation records, and this stage does not treat them as an employee assignment.</p>
    </section>
@endsection
