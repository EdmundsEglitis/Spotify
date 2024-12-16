<?php


namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Log;
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

    // Make a POST request to Spotify's token endpoint
    $response = Http::asForm()->post('https://accounts.spotify.com/api/token', [
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => env('SPOTIFY_REDIRECT_URI'),
        'client_id' => env('SPOTIFY_CLIENT_ID'),
        'client_secret' => env('SPOTIFY_CLIENT_SECRET'),
    ]);

    // Check if the response is successful
    if ($response->successful
    ()) {
        // Decode the response body and get the access token
        $accessToken = $response->json()['access_token'] ?? null;

        if ($accessToken) {
            session(['spotify_access_token' => $accessToken]);
        }
    }

    // Redirect to the moods index route after storing the token
    return redirect()->route('moods.index');
}

    // Store the mood and get playlist recommendations
// Store the mood and get playlist recommendations
public function store(Request $request)
{
    $request->validate([
        'mood' => 'required|string',
    ]);

    $mood = $request->input('mood');
    
    // Get Spotify recommendations based on the mood
    $spotifyData = $this->getSpotifyRecommendations($mood);
    dd($spotifyData);
    // Default to '#' if no playlist URL is found
    $playlistUrl = '#';

    // Check if we have valid data in the response
    if (isset($spotifyData['tracks'][0]['external_urls']['spotify'])) {
        $playlistUrl = $spotifyData['tracks'][0]['external_urls']['spotify'];
    }

    // Log the playlist URL for debugging
    Log::debug('Playlist URL', ['playlist_url' => $playlistUrl]);

    // Save the mood and playlist URL to the database
    MoodHistory::create([
        'user_id' => auth()->id(),
        'mood' => $mood,
        'playlist_url' => $playlistUrl,
    ]);

    // Redirect to the history page with a success message
    return redirect()->route('moods.history')->with('success', 'Playlist recommended!');
}

// Helper function to get Spotify recommendations based on mood
private function getSpotifyRecommendations($mood)
{
    // Get the access token from the session
    $accessToken = session('spotify_access_token');

    // Check if the access token is present
    if (!$accessToken) {
        dd('Spotify access token is missing.');
    }

    // Define a mapping of moods to genres (with valid genres)
    $moodGenreMap = [
        'happy' => 'pop',           // Use 'pop' for happy mood
        'sad' => 'acoustic',        // Use 'acoustic' for sad mood
        'energetic' => 'rock',      // Use 'rock' for energetic mood
        'calm' => 'jazz',           // Use 'jazz' for calm mood
    ];

    // Map the mood to a genre, default to 'pop' if mood is not found
    $genre = $moodGenreMap[strtolower($mood)] ?? 'pop';



    // Make the request to the Spotify API for recommendations
    try {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken, // Use the token here
        ])->get('https://api.spotify.com/v1/recommendations', [
            'seed_genres' => $genre, // Pass the genre as a seed
            'limit' => 5, // Limit the number of recommendations
        ]);
        

        // Dump the response status and body for debugging
        dd('Spotify API Response:', $response->status(), $response->body());

        // Check if the request was successful (HTTP 200)
        if ($response->successful()) {
            $data = $response->json();

            // Dump the response data to see what we got from Spotify
            dd('Response Data:', $data);

            // Check if the data contains tracks
            if (isset($data['tracks']) && count($data['tracks']) > 0) {
                return $data; // Return the tracks data if available
            } else {
                dd('No tracks found in the response.');
                return []; // Return empty if no tracks found
            }
        } else {
            dd('Failed to fetch Spotify recommendations. Response:', $response->status(), $response->body());
            return []; // Return empty if the request failed
        }

    } catch (\Exception $e) {
        // Dump the exception if there's an error with the request
        dd('Error during Spotify API request:', $e->getMessage());
        return []; // Return empty if there's an exception
    }
}




    // Display the user's mood and playlist history
    public function history()
    {
        $histories = MoodHistory::where('user_id', auth()->id())->latest()->get();
        return view('moods.history', compact('histories'));

    }
    public function mooddash()
    {
        dd(session()->all());
        return view('moods.dash');

    }

    // Get Spotify recommendations based on mood

}
