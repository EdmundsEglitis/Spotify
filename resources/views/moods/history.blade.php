
<div class="container">
    <h2>Your Mood & Playlist History</h2>
    <ul>
        @foreach($histories as $history)
            <li>{{ $history->mood }} - <a href="{{ $history->playlist_url }}" target="_blank">Playlist</a> ({{ $history->created_at->format('d M Y, H:i') }})</li>
        @endforeach
    </ul>
</div>
