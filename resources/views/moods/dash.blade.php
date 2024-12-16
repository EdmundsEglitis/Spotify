<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navbar</title>
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
        }
    </style>
</head>
<body>

    <div class="navbar">
    <a href="{{ route('moods.dash') }}" class="logo">MoodApp</a>
        <div class="nav-links">
            <a href="{{ route('moods.history') }}">Mood History</a>
            <a href="{{ route('spotify.login') }}">Login with Spotify</a>
            <a href="{{ route('moods.store') }}">Submit Mood</a>
        </div>
    </div>
