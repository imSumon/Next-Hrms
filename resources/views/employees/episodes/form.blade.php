<form class="card grid two" method="post" action="{{ $action }}">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif
    <div>
        <label for="joining_date">Joining date</label>
        <input id="joining_date" name="joining_date" type="date" value="{{ old('joining_date', $episode->joining_date ?? '') }}" required>
        @error('joining_date')<p class="field-error">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="episode_status">Status</label>
        <select id="episode_status" name="episode_status" required>
            @foreach (['Open', 'inactive', 'separated'] as $status)
                <option value="{{ $status }}" @selected(old('episode_status', $episode->episode_status ?? 'Open') === $status)>{{ $status }}</option>
            @endforeach
        </select>
        @error('episode_status')<p class="field-error">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="last_working_date">Last working date</label>
        <input id="last_working_date" name="last_working_date" type="date" value="{{ old('last_working_date', $episode->last_working_date ?? '') }}">
        @error('last_working_date')<p class="field-error">{{ $message }}</p>@enderror
    </div>
    <div>
        <button type="submit">Save episode</button>
    </div>
</form>
