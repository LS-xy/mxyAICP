<?php
require_once __DIR__ . '/inc/helpers.php';

$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$code = '';
for ($i = 0; $i < 4; $i++) {
    $code .= $chars[random_int(0, strlen($chars) - 1)];
}
$_SESSION['captcha'] = strtoupper($code);
$_SESSION['captcha_at'] = time();

header('Content-Type: image/svg+xml; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$w = 132;
$h = 44;
echo '<svg xmlns="http://www.w3.org/2000/svg" width="' . $w . '" height="' . $h . '" viewBox="0 0 ' . $w . ' ' . $h . '">';
echo '<rect width="' . $w . '" height="' . $h . '" rx="9" fill="#f4f5f7"/>';

for ($i = 0; $i < 5; $i++) {
    $x1 = random_int(0, $w);
    $y1 = random_int(0, $h);
    $x2 = random_int(0, $w);
    $y2 = random_int(0, $h);
    echo '<line x1="' . $x1 . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2 . '" stroke="#c9ccd3" stroke-width="' . (random_int(6, 12) / 10) . '" stroke-linecap="round"/>';
}

for ($i = 0; $i < strlen($code); $i++) {
    $x = 18 + $i * 28;
    $y = 31 + random_int(-3, 3);
    $rot = random_int(-16, 16);
    $size = random_int(20, 24);
    $color = ['#141414', '#3b3f46', '#5a6068'][random_int(0, 2)];
    echo '<text x="' . $x . '" y="' . $y . '" fill="' . $color . '" font-family="ui-monospace, Menlo, Consolas, monospace" font-size="' . $size . '" font-weight="600" transform="rotate(' . $rot . ' ' . $x . ' ' . ($y - 8) . ')">' . $code[$i] . '</text>';
}

for ($i = 0; $i < 18; $i++) {
    echo '<circle cx="' . random_int(0, $w) . '" cy="' . random_int(0, $h) . '" r="' . (random_int(5, 12) / 10) . '" fill="#d3d6dc"/>';
}

echo '</svg>';
