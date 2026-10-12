<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log in — Universal HRMS</title>
    <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
</head>
<body>
    <main class="wrap">
        <h1>Log in</h1>
        <p class="muted">Use the account issued for your company membership.</p>
        @if ($errors->any())
            <div class="errors" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form class="card grid" method="post" action="{{ route('login.store') }}">
            @csrf
            <div>
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required>
            </div>
            <div>
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </div>
            <div>
                <button type="submit">Log in</button>
            </div>
        </form>
    </main>
</body>
</html>
