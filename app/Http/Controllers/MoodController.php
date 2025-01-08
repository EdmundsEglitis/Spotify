<?php

namespace App\Http\Controllers;

use App\Models\History;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MoodController extends Controller
{
    public function index()
    {
        return view("/moods/index");
    }

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

        if ($response->successful()) {
            $accessToken = $response->json()['access_token'] ?? null;

            if ($accessToken) {
                session(['spotify_access_token' => $accessToken]);
            }
        }

        return redirect()->route('moods.index');
    }

    public function store(Request $request)
    {   
        dd($request);
        $request->validate([
            'mood' => 'required|string',
        ]);

        // Detect mood either via form input or using Azure Face API
        $mood = $this->detectEmotionFromAzure($request);
        dd($mood);

        // Fetch Spotify recommendations based on the detected mood
        $spotifyData = $this->getSpotifyRecommendations($mood);
        dd($spotifyData);   
        $playlistUrl = $spotifyData;

        if (isset($spotifyData['tracks'][0]['external_urls']['spotify'])) {
            $playlistUrl = $spotifyData['tracks'][0]['external_urls']['spotify'];
        }

        Log::debug('Playlist URL', ['playlist_url' => $playlistUrl]);

        MoodHistory::create([
            'user_id' => auth()->id(),
            'mood' => $mood,
            'playlist_url' => $playlistUrl,
        ]);

        return redirect()->route('moods.history')->with('success', 'Playlist recommended!');
    }

    private function getSpotifyRecommendations($mood)
    {
        // Hardcoded playlists for a wide range of emotions
        $moodPlaylistMap = [
            'happy' => 'https://open.spotify.com/playlist/37i9dQZF1DXdPec7aLTmlC',      // Happy Vibes Playlist
            'sad' => 'https://open.spotify.com/playlist/37i9dQZF1DWVV27DiNWxkR',        // Sad Songs Playlist
            'angry' => 'https://open.spotify.com/playlist/37i9dQZF1DWY6UWUOwj4BO',      // Angry Metal Playlist
            'bored' => 'https://open.spotify.com/playlist/37i9dQZF1DXcBWIGoYBM5M',      // Chill Hits Playlist
            'energetic' => 'https://open.spotify.com/playlist/37i9dQZF1DX76Wlfdnj7AP',  // Workout Playlist
            'calm' => 'https://open.spotify.com/playlist/37i9dQZF1DX2pSTOxoPbx9',       // Calm Vibes Playlist
            'relaxed' => 'https://open.spotify.com/playlist/37i9dQZF1DWVLO2CENRWhZ',    // Relax & Unwind Playlist
            'motivated' => 'https://open.spotify.com/playlist/37i9dQZF1DX76Wlfdnj7AP',  // Motivation Mix Playlist
            'romantic' => 'https://open.spotify.com/playlist/37i9dQZF1DXb9rZ2GxztEJ',   // Love Songs Playlist
            'nostalgic' => 'https://open.spotify.com/playlist/37i9dQZF1DWSqBruwoIXkA',  // Throwback Hits Playlist
            'lonely' => 'https://open.spotify.com/playlist/37i9dQZF1DX7gIoKXt0gmx',     // Lonely Moments Playlist
            'confident' => 'https://open.spotify.com/playlist/37i9dQZF1DX4qKWGR9z0LI',  // Confidence Boost Playlist
            'focused' => 'https://open.spotify.com/playlist/37i9dQZF1DX8Uebhn9wzrS',    // Focus Flow Playlist
            'stressed' => 'https://open.spotify.com/playlist/37i9dQZF1DWXe9gFZP0gtP',   // Stress Relief Playlist
            'excited' => 'https://open.spotify.com/playlist/37i9dQZF1DX3rxVfibe1L0',    // Excited Pop Hits Playlist
            'grateful' => 'https://open.spotify.com/playlist/37i9dQZF1DX8gDIpdqp1XJ',   // Gratitude Playlist
            'mellow' => 'https://open.spotify.com/playlist/37i9dQZF1DWVmps5U8gHNv',     // Mellow Vibes Playlist
            'uplifted' => 'https://open.spotify.com/playlist/37i9dQZF1DXdxcBWuJkbcy',   // Uplifting Pop Playlist
            'melancholy' => 'https://open.spotify.com/playlist/37i9dQZF1DX9uKNf5jGX6m', // Melancholy Tunes Playlist
            'peaceful' => 'https://open.spotify.com/playlist/37i9dQZF1DWUzFXarNiofw',   // Peaceful Piano Playlist
            'fearful' => 'https://open.spotify.com/playlist/37i9dQZF1DX3SPKpXb5pVw',    // Dark and Fearful Playlist
            'determined' => 'https://open.spotify.com/playlist/37i9dQZF1DX76Wlfdnj7AP', // Determination Playlist
            'playful' => 'https://open.spotify.com/playlist/37i9dQZF1DXa2PvUpywmrr',    // Playful Pop Playlist
            'inspired' => 'https://open.spotify.com/playlist/37i9dQZF1DX1s9knjP51Oa',   // Inspiration Mix Playlist
            'frustrated' => 'https://open.spotify.com/playlist/37i9dQZF1DX6xZZEgC9Ubl', // Frustration Release Playlist
            'curious' => 'https://open.spotify.com/playlist/37i9dQZF1DX2S1fGAdzHbA',    // Curious Sounds Playlist
            'silly' => 'https://open.spotify.com/playlist/37i9dQZF1DWZwtERXCS82H',      // Silly & Fun Playlist
        ];
    
        // Default playlist if mood is not recognized
        return $moodPlaylistMap[strtolower($mood)] ?? 'https://open.spotify.com/playlist/37i9dQZF1DX4sWSpwq3LiO'; // Default Mood Playlist
    }

    // New method to detect emotion using Azure Face API
    private function detectEmotionFromAzure(Request $request)
    {
        // Assuming you're sending a base64 image to detect the emotion
        $imageData = $request->input('image'); // Base64 image data
    
        $apiKey = env('AZURE_FACE_API_KEY');
        $apiEndpoint = env('AZURE_FACE_API_ENDPOINT');
        
        try {
            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $apiKey,
                'Content-Type' => 'application/octet-stream',
            ])->post($apiEndpoint, [
                'body' => base64_decode($imageData),  // Ensure you're decoding the base64 data
            ]);
        
            if ($response->successful()) {
                $data = $response->json();
                $emotion = $this->extractDominantEmotion($data);
                return $emotion;
            } else {
                Log::error('Error detecting emotion from Azure API', ['response' => $response->body()]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Error detecting emotion', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Helper method to extract the dominant emotion
    private function extractDominantEmotion($data)
    {
        if (!isset($data[0]['faceAttributes']['emotion'])) {
            return null;
        }

        $emotions = $data[0]['faceAttributes']['emotion'];
        $dominantEmotion = array_keys($emotions, max($emotions))[0]; // Get the emotion with the highest score
        
        return $dominantEmotion;
    }

    public function getProfile()
    {
        $accessToken = session('spotify_access_token');

        if (!$accessToken) {
            return 'Access token is missing.';
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get('https://api.spotify.com/v1/me');

            if ($response->successful()) {
                $data = $response->json();
                return $data;
            } else {
                return 'Failed to fetch profile. Status: ' . $response->status() . ', Error: ' . $response->body();
            }
        } catch (\Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    public function history()
    {
        $histories = History::where('user_id', auth()->id())->latest()->get();
        return view('moods.history', compact('histories'));
    }

    public function mooddash()
    {
        
        return view('moods.dash');
        
    }
}
