<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mood Detection</title>
    <style>
        /* Global styles */
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #2d2d2d;
            color: white;
        }

        /* Navigation bar styles */
        nav {
            background-color: #1a1a1a;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }

        nav a {
            text-decoration: none;
            color: white;
            font-size: 18px;
            margin: 0 10px;
            transition: color 0.3s;
        }

        nav a:hover {
            color: #007bff;
        }

        nav ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
        }

        nav li {
            margin-left: 15px;
        }

        nav button {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            transition: color 0.3s;
        }

        nav button:hover {
            color: #007bff;
        }

        /* Main content styles */
        .container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: calc(100vh - 50px); /* Adjust height to account for the navbar */
            background-color: #2d2d2d;
        }

        h1 {
            font-size: 2rem;
            margin-bottom: 20px;
        }

        video {
            border: 3px solid white;
            border-radius: 10px;
            max-width: 90%;
            margin-bottom: 20px;
        }

        button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #0056b3;
        }

        form {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav>
        <a href="{{ route('moods.show') }}" class="logo">Mood Detector</a>
        <ul>
            <li><a href="{{ route('moods.history') }}">Mood History</a></li>
            <li><a href="{{ route('spotify.login') }}">Login with Spotify</a></li>
            <li>
                <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                    @csrf
                    <button type="submit">Logout</button>
                </form>
            </li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <h1>Mood Detection</h1>
        <video id="video" autoplay></video>
        <button id="capture">Capture Mood</button>

        <form id="image-form" action="{{ route('moods.detect') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="image" id="image">
            <button type="submit" style="display: none;">Submit</button>
        </form>
    </div>

    <script>
        const video = document.getElementById('video');
        const capture = document.getElementById('capture');
        const imageForm = document.getElementById('image-form');
        const imageInput = document.getElementById('image');

        navigator.mediaDevices.getUserMedia({ video: true })
            .then(stream => {
                video.srcObject = stream;
            })
            .catch(err => {
                console.error('Error accessing webcam: ', err);
            });

        capture.addEventListener('click', () => {
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            canvas.toBlob(blob => {
                const reader = new FileReader();
                reader.readAsDataURL(blob);
                reader.onloadend = () => {
                    imageInput.value = reader.result;
                    imageForm.submit();
                };
            });
        });
    </script>
</body>
</html>
