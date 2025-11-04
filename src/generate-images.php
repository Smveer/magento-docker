<?php
// generate-images.php
// Crée des images factices avec le nom du fichier inscrit dessus.

$targetDir = __DIR__ . '/app/design/frontend/Imagin/Theme/web/images';
if (!is_dir($targetDir)) {
    if (!mkdir($targetDir, 0775, true)) {
        echo "Erreur : impossible de créer le répertoire $targetDir\n";
        exit(1);
    }
}

// Liste des fichiers à générer
$files = [
    'hero.jpg',
    'residential.jpg',
    'murs-occupes.jpg',
    'renover.jpg',
    'street.jpg',
    'commercial.jpg',
    'hcr.jpg',
    'services.jpg'
];

// Taille des images
$w = 1200;
$h = 600;

// Couleurs de fond différentes (will cycle)
$bgColors = [
    [46, 204, 113],   // green
    [52, 152, 219],   // blue
    [231, 76, 60],    // red
    [241, 196, 15],   // yellow
    [155, 89, 182],   // purple
    [230, 126, 34],   // orange
    [52, 73, 94],     // dark slate
    [149, 165, 166],  // gray
];

// Police TrueType possible paths (common linux locations)
$possibleFonts = [
    '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
    '/usr/share/fonts/truetype/freefont/FreeSans.ttf',
    '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
    __DIR__ . '/DejaVuSans.ttf', // local fallback if you upload font
];

$fontFile = null;
foreach ($possibleFonts as $p) {
    if (file_exists($p) && is_readable($p)) {
        $fontFile = $p;
        break;
    }
}

// Function to center TTF text
function imagettf_centered($img, $size, $angle, $font, $text, $color) {
    $bbox = imagettfbbox($size, $angle, $font, $text);
    $textW = abs($bbox[2] - $bbox[0]);
    $textH = abs($bbox[7] - $bbox[1]);
    $imgW = imagesx($img);
    $imgH = imagesy($img);
    $x = ($imgW - $textW) / 2;
    // adjust y baseline
    $y = ($imgH + $textH) / 2;
    imagettftext($img, $size, $angle, (int)$x, (int)$y, $color, $font, $text);
}

// Loop create images
foreach ($files as $i => $fname) {
    $img = imagecreatetruecolor($w, $h);

    // Background color pick
    $bg = $bgColors[$i % count($bgColors)];
    $bgColor = imagecolorallocate($img, $bg[0], $bg[1], $bg[2]);
    imagefilledrectangle($img, 0, 0, $w, $h, $bgColor);

    // Slight overlay to make text pop
    $overlay = imagecolorallocatealpha($img, 0, 0, 0, 50); // semi transparent black
    imagefilledrectangle($img, 0, (int)($h * 0.65), $w, $h, $overlay);

    // Text color: white
    $white = imagecolorallocate($img, 255, 255, 255);

    // Compose label: file name without extension, capitalized words
    $label = pathinfo($fname, PATHINFO_FILENAME);
    // replace dashes with spaces and uppercase words
    $label = str_replace('-', ' ', $label);
    $label = preg_replace('/\s+/', ' ', trim($label));
    $label = mb_convert_case($label, MB_CASE_TITLE, "UTF-8");

    // If TTF available, use it larger and centered; else fallback to imagestring
    if ($fontFile) {
        // Big title
        $sizeTitle = 48;
        imagettf_centered($img, $sizeTitle, 0, $fontFile, $label, $white);

        // Smaller subtitle with filename
        $sub = $fname;
        $sizeSub = 20;
        $bbox = imagettfbbox($sizeSub, 0, $fontFile, $sub);
        $textW = abs($bbox[2] - $bbox[0]);
        $textH = abs($bbox[7] - $bbox[1]);
        $x = ($w - $textW) / 2;
        $y = ($h + $textH) / 2 + 48; // push below the title
        imagettftext($img, $sizeSub, 0, (int)$x, (int)$y, $white, $fontFile, $sub);
    } else {
        // fallback: use built-in font, write twice (title+filename)
        $font = 5; // builtin font size
        $text = $label;
        $text2 = $fname;
        // calculate positions
        $textW = imagefontwidth($font) * strlen($text);
        $textH = imagefontheight($font);
        $x = (int)(($w - $textW) / 2);
        $y = (int)(($h - $textH) / 2) - 10;
        imagestring($img, $font, $x, $y, $text, $white);

        $textW2 = imagefontwidth($font) * strlen($text2);
        $x2 = (int)(($w - $textW2) / 2);
        imagestring($img, $font, $x2, $y + 30, $text2, $white);
    }

    // Save as JPEG with quality 85
    $out = $targetDir . '/' . $fname;
    if (imagejpeg($img, $out, 85)) {
        echo "Created: $out\n";
    } else {
        echo "Failed to create: $out\n";
    }

    imagedestroy($img);
}

echo "Done.\n";
