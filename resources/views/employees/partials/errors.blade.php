@if ($errors->any())
    <div class="errors" role="alert">
        <strong>Check the form.</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
