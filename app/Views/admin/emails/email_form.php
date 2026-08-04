<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Email</title>
</head>
<body>
    <h2>Send Email</h2>
    <!-- Email sending form -->
    <form action="<?php echo base_url('sadmin/send'); ?>" method="post">
        <label for="to">To:</label>
        <input type="email" name="to" id="to" required><br><br>

        <label for="subject">Subject:</label>
        <input type="text" name="subject" id="subject" required><br><br>

        <label for="message">Message:</label><br>
        <textarea name="message" id="message" rows="5" required></textarea><br><br>

        <button type="submit">Send Email</button>
    </form>
</body>
</html>
