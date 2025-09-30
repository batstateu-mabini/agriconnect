<?php
$response = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $recipient = isset($_POST['recipient']) ? trim($_POST['recipient']) : '';
  $message = isset($_POST['message']) ? trim($_POST['message']) : '';
  $sender_id = 'PhilSMS';
  $token = '959|sTvinSqCTo4H41HoogCFyggNenkamLKcjvrQwRlP ';

  if ($recipient && $message) {
    $send_data = [
      'sender_id' => $sender_id,
      'recipient' => $recipient,
      'message' => $message
    ];
    $parameters = json_encode($send_data);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://app.philsms.com/api/v3/sms/send');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $headers = [
      'Content-Type: application/json',
      "Authorization: Bearer $token"
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $get_sms_status = curl_exec($ch);
    if ($get_sms_status === false) {
      $response = 'cURL error: ' . curl_error($ch);
    } else {
      $response = $get_sms_status;
    }
    curl_close($ch);
  } else {
    $response = 'Please enter both recipient and message.';
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Send SMS</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 40px; }
    form { max-width: 400px; margin: auto; padding: 20px; border: 1px solid #ccc; border-radius: 8px; }
    label { display: block; margin-bottom: 8px; }
    input, textarea { width: 100%; margin-bottom: 16px; padding: 8px; }
    button { padding: 10px 20px; }
    .response { max-width: 400px; margin: 20px auto; background: #f9f9f9; border: 1px solid #eee; padding: 10px; border-radius: 8px; }
  </style>
</head>
<body>
  <form method="POST">
    <label for="recipient">Recipient Number:</label>
    <input type="text" id="recipient" name="recipient" placeholder="e.g. +639123456789" required>
    <label for="message">Message:</label>
    <textarea id="message" name="message" rows="4" placeholder="Type your message here..." required></textarea>
    <button type="submit">Send SMS</button>
  </form>
  <?php if (!empty($response)): ?>
    <div class="response"><strong>Response:</strong><br><?php echo htmlspecialchars($response); ?></div>
  <?php endif; ?>
</body>
</html>
