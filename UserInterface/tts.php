

<?php
$text = $_GET['text'] ?? 'สวัสดีครับ';
$apiKey = 'AIzaSyA9RbgxksrO-byOFGVJIZe9TMCN7TeXbnw'; // ใส่ API Key ของคุณ
$url = "https://texttospeech.googleapis.com/v1/text:synthesize?key=$apiKey";

$data = [
    "input" => ["text" => $text],
    "voice" => ["languageCode" => "th-TH", "ssmlGender" => "FEMALE"],
    "audioConfig" => ["audioEncoding" => "MP3"]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode != 200) {
    die("Error calling TTS API, HTTP code: $httpCode");
}

$resData = json_decode($response, true);
$audioContent = base64_decode($resData['audioContent']);
file_put_contents('tts.mp3', $audioContent);
echo 'tts.mp3';
?>
