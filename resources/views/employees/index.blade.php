@extends('layouts.hrms')

@section('content')
    <div class="toolbar">
        <div>
            <h1>Employees</h1>
            <p class="muted">{{ $company->company_name }}</p>
        </div>
        @if ($canManageEmployees)
            <a class="button" href="{{ route('employees.create') }}">Add employee</a>
        @endif
    </div>

    <form class="card" method="get" action="{{ route('employees.index') }}">
        <label for="q">Search by employee code or display name</label>
        <div class="toolbar">
            <input id="q" name="q" value="{{ $search }}" autocomplete="off">
            <button type="submit">Search</button>
        </div>
    </form>

    <div class="card table-scroll">
        @if ($employees->isEmpty())
            <p class="empty">{{ $search === '' ? 'No employees in this company.' : 'No employees match this search.' }}</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Employee code</th>
                        <th>Display name</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($employees as $employee)
                        <tr>
                            <td>{{ $employee->employee_code }}</td>
                            <td>{{ $employee->display_name }}</td>
                            <td><a href="{{ route('employees.show', $employee) }}">View</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div>{{ $employees->links() }}</div>
        @endif
    </div>
@endsection
