<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header img {
            max-width: 150px;
        }
        .content {
            text-align: left;
            margin-bottom: 20px;
        }
        .code {
            font-size: 24px;
            font-weight: bold;
            color: #1CA686;
            margin: 20px 0;
            text-align: center;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset('images/logo.png') }}" alt="Application Logo">
        </div>
        <div class="content">
            <h2>Password Reset Request</h2>
            <p>You requested to reset your password.</p>
            <p>Please use the verification code below to confirm your email address:</p>
            <div class="code">{{ $user->verification_code }}</div>
            <p>If you did not request this, you can ignore this email.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} Our Application. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
