<?php

session_start();

$captcha = substr(
    str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'),
    0,
    5
);

$_SESSION['captcha'] = $captcha;

header('Content-Type: image/png');

$image = imagecreate(150, 50);

$background = imagecolorallocate(
    $image,
    2,
    31,
    66
);

$textColor = imagecolorallocate(
    $image,
    0,
    212,
    216
);

$lineColor = imagecolorallocate(
    $image,
    22,
    126,
    128
);

for ($i = 0; $i < 5; $i++) {
    imageline(
        $image,
        rand(0,150),
        rand(0,50),
        rand(0,150),
        rand(0,50),
        $lineColor
    );
}

imagestring(
    $image,
    5,
    35,
    18,
    $captcha,
    $textColor
);

imagepng($image);

imagedestroy($image);