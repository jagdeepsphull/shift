<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Email</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: #4CAF50;
            color: white;
            text-align: center;
            padding: 20px;
        }
        .content {
            padding: 20px;
        }
        .content h1 {
            color: #333;
        }
        .content p {
            line-height: 1.6;
        }
        .cta {
            text-align: center;
            margin-top: 20px;
        }
        .cta a {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px 0;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .footer {
            text-align: center;
            background: #f1f1f1;
            padding: 10px;
            font-size: 0.9em;
            color: #666;
        }
        .footer a {
            color: #4CAF50;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to <?= $settings[0]->s_sitename; ?>!</h1>
        </div>
        <div class="content">
            <h1>Hello, <?= $name ?>!</h1>
            <p>Your account is currently under review and will be activated upon approval. Once approved, you will receive a confirmation email with further details.</p>
			<p>In the meantime, if you have any questions, feel free to reach out to our support team</p>
			<p>We appreciate your patience and look forward to having you as part of our community!</p>
            
        </div>
        <div class="footer">
            <p>If you have any questions, feel free to <a href="mailto:<?= $settings[0]->s_email; ?>">contact us</a>. We're here to help!</p>
            <p>&copy; <?= date('Y'); ?> <?= $settings[0]->s_sitename; ?>. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
