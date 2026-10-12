@extends('layouts.hrms')

@section('content')
    <h1>Add employee</h1>
    <p class="muted">{{ $company->company_name }}. The company is fixed by the signed-in context.</p>
    <p class="row-actions">
        <a href="{{ route('employees.index') }}">Back to directory</a>
    </p>
    <p class="note">Employee code and display name create the employee identity. Joining date, episode status, and last working date belong to the first employment episode and may all be left empty. If you enter any of those episode details, the current validation still applies.</p>
    @include('employees.partials.errors')
    <form class="card grid two" method="post" action="{{ route('employees.store') }}">
        @csrf
        <div>
            <label for="employee_code">Employee code</label>
            <input id="employee_code" name="employee_code" value="{{ old('employee_code') }}" required>
            @error('employee_code')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="display_name">Display name</label>
            <input id="display_name" name="display_name" value="{{ old('display_name') }}" required>
            @error('display_name')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="joining_date">Joining date</label>
            <input id="joining_date" name="joining_date" type="date" value="{{ old('joining_date') }}">
            @error('joining_date')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="episode_status">Episode status</label>
            <select id="episode_status" name="episode_status">
                <option value="">No episode yet</option>
                @foreach (['Open', 'inactive', 'separated'] as $status)
                    <option value="{{ $status }}" @selected(old('episode_status') === $status)>{{ $status }}</option>
                @endforeach
            </select>
            @error('episode_status')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="last_working_date">Last working date</label>
            <input id="last_working_date" name="last_working_date" type="date" value="{{ old('last_working_date') }}">
            <p class="muted">Leave empty for an Open episode. A check that this date follows the joining date is not approved.</p>
            @error('last_working_date')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <button type="submit">Save employee</button>
        </div>
    </form>
@endsection
