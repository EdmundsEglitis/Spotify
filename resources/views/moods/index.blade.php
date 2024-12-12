
<div class="container">
    <h2>Enter Your Mood</h2>
    <form action="{{ route('moods.store') }}" method="POST">
        @csrf
        <input type="text" name="mood" placeholder="Enter your mood" required>
        <button type="submit">Get Playlist</button>
    </form>
    <a href="{{ route('spotify.login') }}">Link Spotify Account</a>
</div>

