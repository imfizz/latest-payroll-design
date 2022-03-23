<?php

require '../vendor/autoload.php';

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;

$writer = new PngWriter();

// Create QR code
$qrCode = QrCode::create($_GET['myqr'])
    ->setEncoding(new Encoding('UTF-8'))
    ->setErrorCorrectionLevel(new ErrorCorrectionLevelLow())
    ->setSize(300)
    ->setMargin(10)
    ->setRoundBlockSizeMode(new RoundBlockSizeModeMargin())
    ->setForegroundColor(new Color(0, 0, 0))
    ->setBackgroundColor(new Color(255, 255, 255));

$result = $writer->write($qrCode);

// Directly output the QR code
// header('Content-Type: '.$result->getMimeType());
// echo $result->getString();

// Save it to a file
$result->saveToFile(__DIR__.'/qrcode.png');

// Generate a data URI to include image data inline (i.e. inside an <img> tag)
// $dataUri = $result->getDataUri();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script type="text/javascript">
        
    </script>
    <style>
        #mainImg {height: 300px; width: 300px; }
    </style>
</head>
<body>
    <img src='./qrcode.png' id='mainImg' alt="">
    <button onclick="printImg()">Print Qr</button>
    <script>
        
        
        

        function printImg() {
            var popup;

            popup = window.open(document.getElementById("mainImg").src);
            popup.onload = function (){ popup.print(); }
            popup.onbeforeunload = setTimeout(function () { popup.close(); },500);
            popup.onafterprint = setTimeout(function () { popup.close(); },500);
            popup.focus(); // Required for IE
        }
    </script>
</body>
</html>