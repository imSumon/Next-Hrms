@extends('layouts.hrms')

@section('title', 'Choose a company')

@section('content')
    <h1>Choose a company</h1>
    <p class="muted">Only companies with an active membership are listed.</p>
    @include('employees.partials.errors')
    <form class="card grid" method="post" action="{{ route('company.switch') }}">
        @csrf
        <div>
            <label for="company_id">Company</label>
            <select id="company_id" name="company_id" required>
                @foreach ($memberships as $membership)
                    <option value="{{ $membership->company_id }}" @selected(session('company_context_id') === $membership->company_id)>{{ $membership->company->company_name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <button type="submit">Continue</button>
        </div>
    </form>
@endsection
