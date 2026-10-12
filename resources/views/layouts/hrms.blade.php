<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Employees') — Universal HRMS</title>
    <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
</head>
<body>
    <header class="site">
        <div class="wrap">
            <strong>Universal HRMS</strong>
            <nav>
                @auth
                    @if (($navMemberships ?? collect())->count() > 1)
                        <form method="post" action="{{ route('company.switch') }}">
                            @csrf
                            <label class="visually-hidden" for="company_id">Company</label>
                            <select id="company_id" name="company_id" onchange="this.form.submit()">
                                @foreach ($navMemberships as $membership)
                                    <option value="{{ $membership->company_id }}" @selected(session('company_context_id') === $membership->company_id)>{{ $membership->company->company_name }}</option>
                                @endforeach
                            </select>
                        </form>
                    @elseif (($navMemberships ?? collect())->count() === 1)
                        <span>{{ $navMemberships->first()->company->company_name }}</span>
                    @endif
                    @if (session('company_context_id'))
                        <a href="{{ route('employees.index') }}">Employees</a>
                    @endif
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit">Log out</button>
                    </form>
                    <span>{{ auth()->user()->name }}</span>
                @endauth
            </nav>
        </div>
    </header>
    <main class="wrap">
        @if (session('status'))
            <p class="ok">{{ session('status') }}</p>
        @endif
        @yield('content')
    </main>
</body>
</html>
