<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emotion Detection</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .navbar {
            background-color: #1f2937;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .navbar a {
            color: #ffffff;
            text-decoration: none;
            margin-right: 1.5rem;
            font-size: 16px;
            font-weight: 600;
            transition: color 0.3s;
        }

        .navbar a:hover {
            color: #60a5fa;
        }

        .navbar .logo {
            font-size: 20px;
            font-weight: bold;
        }

        .nav-links {
            display: flex;
        }

        .container {
            margin: 2rem auto;
            padding: 2rem;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            text-align: center;
        }

        #video {
            width: 100%;
            max-width: 400px;
            border-radius: 12px;
            margin-bottom: 1rem;
            border: 1px solid #ccc;
        }

        #capture-btn, #detect-btn {
            padding: 10px 20px;
            background-color: #1f2937;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin: 10px;
        }

        #capture-btn:hover, #detect-btn:hover {
            background-color: #60a5fa;
        }

        #emotion-result {
            margin-top: 1rem;
            font-size: 18px;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="navbar">
        <a href="#" class="logo">MoodApp</a>
        <div class="nav-links">
            <a href="#">Mood History</a>
            <a href="#">Login with Spotify</a>
        </div>
    </div>

    <div class="container">
        <h2>Detect Your Emotion</h2>
        <video id="video" autoplay></video>
        <button id="capture-btn">Capture Image</button>
        <button id="detect-btn">Detect Emotion</button>
        <canvas id="canvas" style="display: none;"></canvas>
        <div id="emotion-result"></div>
    </div>

    <script>
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const captureBtn = document.getElementById('capture-btn');
    const detectBtn = document.getElementById('detect-btn');
    const emotionResult = document.getElementById('emotion-result');

    // Start webcam video
    navigator.mediaDevices.getUserMedia({ video: true })
        .then(stream => {
            video.srcObject = stream;
        })
        .catch(err => {
            console.error('Error accessing webcam:', err);
            alert('Unable to access webcam.');
        });

    // Capture image from video
    captureBtn.addEventListener('click', () => {
        if (!video.srcObject) {
            alert('Webcam not available.');
            return;
        }
        const context = canvas.getContext('2d');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        alert('Image captured. Click "Detect Emotion" to analyze.');
    });

    // Detect emotion using Azure Face API
    detectBtn.addEventListener('click', async () => {
        if (!canvas.width || !canvas.height) {
            emotionResult.innerText = 'Please capture an image first.';
            return;
        }

        const imageData = canvas.toDataURL('image/jpeg');
        const blob = await fetch(imageData).then(res => res.blob());

        const apiUrl = 'https://edmunds.cognitiveservices.azure.com/face/v1.0/detect?returnFaceAttributes=emotion';
        const apiKey = 'tW9QFz4UsiX1e0ydlpbvZjRuADvSfVluFb9ylWiPyUsUNvOABaCZJQQJ99ALACi5YpzXJ3w3AAAKACOGCtCx';  // Replace with your actual API key

        const headers = {
            'Content-Type': 'application/octet-stream',
            'Ocp-Apim-Subscription-Key': apiKey
        };

        try {
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: headers,
                body: blob
            });

            if (response.status === 403) {
                const errorBody = await response.text();  // Get detailed error message
                console.error('API request failed: Forbidden.', errorBody);
                alert('Forbidden: Please check your API key and permissions.');
                return;
            }

            if (!response.ok) {
                const errorBody = await response.text();  // Log error body
                console.error('API request failed:', response.status, response.statusText, errorBody);
                throw new Error(`API request failed: ${response.statusText}`);
            }

            const data = await response.json();
            console.log('API Response Data:', JSON.stringify(data));

            if (data.length === 0) {
                emotionResult.innerText = 'No face detected. Please try again.';
                return;
            }

            const emotions = data[0]?.faceAttributes?.emotion;
            if (!emotions) {
                emotionResult.innerText = 'No emotions detected. Please try again.';
                return;
            }

            const detectedEmotion = Object.keys(emotions).reduce((a, b) => emotions[a] > emotions[b] ? a : b);
            emotionResult.innerText = `Detected Emotion: ${detectedEmotion}`;

        } catch (error) {
            console.error('Error detecting emotion:', error);
            emotionResult.innerText = 'Error detecting emotion. Please try again.';
        }
    });
</script>

</body>
</html>
