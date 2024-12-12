<?php


namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\MoodHistory;
use Illuminate\Support\Facades\Http;

class MoodController extends Controller
{
    public function index()
    {
        return view("/moods/index");
    }

    // Redirect to Spotify authorization
    public function redirectToSpotify()
    {
         $query = http_build_query([
            'client_id' => env('SPOTIFY_CLIENT_ID'),
            'response_type' => 'code',
            'redirect_uri' => env('SPOTIFY_REDIRECT_URI'),
            'scope' => 'playlist-read-private playlist-read-collaborative',
        ]);

        return redirect('https://accounts.spotify.com/authorize?' . $query);
    }

    // Handle the Spotify callback and get the access token
    public function handleSpotifyCallback(Request $request)
    {
        $code = $request->input('code');

        $response = Http::asForm()->post('https://accounts.spotify.com/api/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => env('SPOTIFY_REDIRECT_URI'),
            'client_id' => env('SPOTIFY_CLIENT_ID'),
            'client_secret' => env('SPOTIFY_CLIENT_SECRET'),
        ]);

        $accessToken = $response->json()['access_token'];
        session(['spotify_access_token' => $accessToken]);
        
        return redirect()->route('moods.index');
    }

    // Store the mood and get playlist recommendations
    public function store(Request $request)
    {
        $request->validate([
            'mood' => 'required|string',
        ]);

        $mood = $request->input('mood');
        $spotifyData = $this->getSpotifyRecommendations($mood);
        $playlistUrl = $spotifyData['tracks'][0]['external_urls']['spotify'] ?? '#';

        MoodHistory::create([
            'user_id' => auth()->id(),
            'mood' => $mood,
            'playlist_url' => $playlistUrl,
        ]);

        return redirect()->route('moods.history')->with('success', 'Playlist recommended!');
    }

    // Display the user's mood and playlist history
    public function history()
    {
        $histories = MoodHistory::where('user_id', auth()->id())->latest()->get();
        return view('moods.history', compact('histories'));
    }

    // Get Spotify recommendations based on mood
    private function getSpotifyRecommendations($mood)
    {
        $accessToken = session('spotify_access_token');

        $moodGenreMap = [
            'happy' => 'pop',
            'sad' => 'blues',
            'energetic' => 'rock',
            'calm' => 'acoustic',
        ];

        $genre = $moodGenreMap[strtolower($mood)] ?? 'pop';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get('https://api.spotify.com/v1/recommendations', [
            'seed_genres' => $genre,
            'limit' => 5,
        ]);

        return $response->json();
    }
}
