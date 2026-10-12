<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Employees unavailable — Universal HRMS</title>
    <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
</head>
<body>
    <main class="wrap">
        <h1>Employees are unavailable</h1>
        <p>This account has no active company membership.</p>
        <p>Signing in does not grant every company. A company id in the address bar or in a form is not a membership.</p>
        @auth
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Log out</button>
            </form>
        @endauth
    </main>
</body>
</html>
