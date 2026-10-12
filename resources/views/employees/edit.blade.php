@extends('layouts.hrms')

@section('content')
    <h1>Edit {{ $employee->display_name }}</h1>
    <p class="muted">Employee code {{ $employee->employee_code }} stays on this record.</p>
    <p class="row-actions">
        <a href="{{ route('employees.show', $employee) }}">Back to profile</a>
    </p>
    @include('employees.partials.errors')
    <form class="card grid" method="post" action="{{ route('employees.update', $employee) }}">
        @csrf
        @method('PUT')
        <div>
            <label for="display_name">Display name</label>
            <input id="display_name" name="display_name" value="{{ old('display_name', $employee->display_name) }}" required>
            @error('display_name')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <button type="submit">Save</button>
        </div>
    </form>
@endsection
