<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth; 
use App\Models\History;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\Likelihood;



class FaceDetectionController extends Controller
{
    public function detect(Request $request)
    {   
        
        $imageData = $request->input('image');

        if (!$imageData) {
            return response()->json(['error' => 'Image data not provided'], 400);
        }

        $imageData = explode(',', $imageData);
        $imageData = base64_decode(end($imageData));
        $tempFilePath = tempnam(sys_get_temp_dir(), 'face_detection');
        file_put_contents($tempFilePath, $imageData);

        $imageAnnotator = new ImageAnnotatorClient([
            'credentials' => json_decode(file_get_contents(env('GOOGLE_APPLICATION_CREDENTIALS')), true)
        ]);

        $features = [new Feature(['type' => Feature\Type::FACE_DETECTION])];

        $response = $imageAnnotator->annotateImage(
            fopen($tempFilePath, 'r'),
            $features
        );

        unlink($tempFilePath);

        $moodScores = $this->analyzeMood($response);
        $moodScoresWithLinks = [];
        $moodToLink = [
            'happy' => 'https://open.spotify.com/playlist/37i9dQZF1EIgG2NEOhqsD7?si=p6lfjivdRcSG23cDMnZoNA',
            'angry' => 'https://open.spotify.com/playlist/37i9dQZF1EIgNZCaOGb0Mi?si=LLAmWr3BTRS4S9jGa5AFqA',
            'sad' => 'https://open.spotify.com/playlist/37i9dQZF1EIeODNDegVpao?si=VBUlgQksSreUSuxYFYb6_A',
            'surprised' => 'https://open.spotify.com/playlist/37i9dQZF1E8Ndx84ry0SXK?si=KdcYMozEQNWPRJTSdp129g',
            'confused' => 'https://open.spotify.com/playlist/0rIiYORcepCIavtLdRTzRS?si=Z349UipdTSu3N5taDqIzMQ',
            'fearful' => 'https://open.spotify.com/playlist/37i9dQZF1EIfMwRYymgnLH?si=JOOYbkYASpeKcmf1PEnqYQ',
            'excited' => 'https://open.spotify.com/playlist/37i9dQZF1E8C9fexScxT8p?si=dj7bRAkzT2W3gMw6wg2eDg',
            'bored' => 'https://open.spotify.com/playlist/37i9dQZF1E8MCNIu2yacHI?si=DRCgFCeeT8S_EFLBOaeO2w',
            'relaxed' => 'https://open.spotify.com/playlist/7JabddFr3Q6JPsND4v9Swf?si=beSCPqisSjOcnQzUspLg9A',
        ];
        foreach ($moodScores as $mood => $score) {
            $moodScoresWithLinks[] = [
                'mood' => $mood,
                'score' => $score,
                'link' => $moodToLink[$mood]
            ];
        }
        
        $user = Auth::user();
        $history = new History([
            'user_id' => $user->id,
            'mood' => $moodScoresWithLinks[0]['mood'],
            'playlist_url' => $moodScoresWithLinks[0]['link'] // Use playlist_url
        ]);
        $history->save();

        return redirect($moodScoresWithLinks[0]['link']);


        // return view('results', ['moodScores' => array_slice($moodScores, 0, 1)]);
        // array_slice($moodScores, 0, 1);
        // $moods = [
        //     'happy' => 0,
        //     'angry' => 0,
        //     'sad' => 0,
        //     'surprised' => 0,
        //     'confused' => 0,
        //     'fearful' => 0,
        //     'excited' => 0,
        //     'bored' => 0,
        //     'relaxed' => 0
        // ];
    }

    private function analyzeMood($response)
    {
        $faces = $response->getFaceAnnotations();
        if (!$faces) {
            return [
                'happy' => 0,
                'angry' => 0,
                'sad' => 0,
                'surprised' => 0,
                // 'calm' => 0,
                'confused' => 0,
                'fearful' => 0,
                // 'disgusted' => 0,
                'excited' => 0,
                'bored' => 0,
                // 'thoughtful' => 0,
                'relaxed' => 0
            ];
        }

        $moodScores = [
            'happy' => 0,
            'angry' => 0,
            'sad' => 0,
            'surprised' => 0,
            // 'calm' => 0,
            'confused' => 0,
            'fearful' => 0,
            // 'disgusted' => 0,
            'excited' => 0,
            'bored' => 0,
            // 'thoughtful' => 0,
            'relaxed' => 0
        ];

        foreach ($faces as $face) {
            $joyLikelihood = $this->normalizeLikelihood($face->getJoyLikelihood());
            $angerLikelihood = $this->normalizeLikelihood($face->getAngerLikelihood());
            $sorrowLikelihood = $this->normalizeLikelihood($face->getSorrowLikelihood());
            $surpriseLikelihood = $this->normalizeLikelihood($face->getSurpriseLikelihood());
            $headwearLikelihood = $this->normalizeLikelihood($face->getHeadwearLikelihood());
            $confidence = $face->getDetectionConfidence();

            $moodScores['happy'] += $joyLikelihood;
            $moodScores['angry'] += $angerLikelihood;
            $moodScores['sad'] += $sorrowLikelihood;
            $moodScores['surprised'] += $surpriseLikelihood;
            // $moodScores['disgusted'] += ($angerLikelihood > 0.4 && $joyLikelihood < 0.2) ? 0.6 : 0;
            $moodScores['excited'] += ($joyLikelihood > 0.6 && $surpriseLikelihood > 0.4) ? 0.7 : 0;
            $moodScores['bored'] += ($joyLikelihood < 0.2 && $sorrowLikelihood < 0.2 && $angerLikelihood < 0.2 && $confidence > 0.7) ? 0.8 : 0;
            // $moodScores['thoughtful'] += ($sorrowLikelihood > 0.3 && $joyLikelihood < 0.4) ? 0.5 : 0;
            $moodScores['relaxed'] += ($joyLikelihood > 0.4 && $sorrowLikelihood < 0.2 && $angerLikelihood < 0.2) ? 0.6 : 0;
            $moodScores['confused'] += ($surpriseLikelihood > 0.5 && $joyLikelihood < 0.3 && $sorrowLikelihood < 0.3) ? 0.5 : 0;
            // $moodScores['calm'] += ($joyLikelihood < 0.3 && $angerLikelihood < 0.3 && $sorrowLikelihood < 0.3 && $surpriseLikelihood < 0.3) ? 0.4 : 0;
            $moodScores['fearful'] += ($confidence < 0.5 && $headwearLikelihood > 0.5) ? 0.3 : 0;
        }

        arsort($moodScores);

        return $moodScores;
    }

    private function normalizeLikelihood($likelihood)
    {
        switch ($likelihood) {
            case Likelihood::VERY_UNLIKELY:
                return 0;
            case Likelihood::UNLIKELY:
                return 0.2;
            case Likelihood::POSSIBLE:
                return 0.4;
            case Likelihood::LIKELY:
                return 0.6;
            case Likelihood::VERY_LIKELY:
                return 0.8;
            default:
                return 0;
        }
    }

    public function show()
    {
        return view('moods.detect');
    }
}