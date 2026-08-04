<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us Message</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <table style="width: 100%; max-width: 600px; margin: auto; border-collapse: collapse; border: 1px solid #ddd;">
        <thead>
            <tr>
                <th style="background-color: #f4f4f4; padding: 15px; border-bottom: 1px solid #ddd; text-align: left; font-size: 18px;">
                    New Contact Us Message
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding: 15px;">
                    <p><strong>Name:</strong> <?= htmlspecialchars($name); ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($email); ?></p>
                    <p><strong>Subject:</strong> <?= htmlspecialchars($subject); ?></p>
                    <p><strong>Message:</strong></p>
                    <p style="white-space: pre-line; background: #f9f9f9; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        <?= nl2br(htmlspecialchars($message)); ?>
                    </p>
                </td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td style="background-color: #f4f4f4; padding: 15px; text-align: center; font-size: 14px; color: #777;">
                    This message was sent via the Contact Us form on your website.
                </td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
