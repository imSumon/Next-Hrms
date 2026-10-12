<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Employee changes are not authorized — Universal HRMS</title>
    <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
</head>
<body>
    <main class="wrap">
        <h1>Employee changes are not authorized</h1>
        <p>This membership can open the directory. Changing employees requires an explicit grant for this module.</p>
        <p>That grant is not a universal role. The role list is not approved.</p>
        <p><a href="{{ route('employees.index') }}">Back to the directory</a></p>
    </main>
</body>
</html>
