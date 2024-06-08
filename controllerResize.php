<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $uploadDir = 'images/';
    $uploadFile = $uploadDir . basename($_FILES['image']['name']);
    $imageType = strtolower(pathinfo($uploadFile, PATHINFO_EXTENSION));

    // Check if image file is an actual image or fake image
    $check = getimagesize($_FILES['image']['tmp_name']);
    if ($check === false) {
        die('File is not an image.');
    }

    // Check file size
    if ($_FILES['image']['size'] > 5000000) { // Limit: 5MB
        die('Sorry, your file is too large.');
    }

    // Allow certain file formats
    if (!in_array($imageType, ['jpg', 'jpeg', 'png', 'gif'])) {
        die('Sorry, only JPG, JPEG, PNG & GIF files are allowed.');
    }

    // Save the uploaded file
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
        die('Sorry, there was an error uploading your file.');
    }

    // Resize and compress the image
    compressImage($uploadFile, $uploadFile, 100);

    // Add overlay
    addOverlay($uploadFile);

    echo 'The file ' . htmlspecialchars(basename($_FILES['image']['name'])) . ' has been uploaded and processed.';
}

function compressImage($source, $destination, $targetSizeKB) {
    $info = getimagesize($source);
    $mime = $info['mime'];

    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($source);
            break;
        default:
            die('Unsupported image type.');
    }

    $quality = 90;
    $scale = 1.0;

    do {
        // Resize image
        $newWidth = imagesx($image) * $scale;
        $newHeight = imagesy($image) * $scale;
        $resizedImage = imagescale($image, $newWidth, $newHeight);

        // Save image
        ob_start();
        imagejpeg($resizedImage, null, $quality);
        $imageData = ob_get_contents();
        ob_end_clean();
        
        // Check the size of the file
        $fileSizeKB = strlen($imageData) / 1024;
        $scale *= 0.9;
        $quality -= 5;
        imagedestroy($resizedImage);

    } while ($fileSizeKB > $targetSizeKB && $quality > 10);

    // Save final image
    file_put_contents($destination, $imageData);
    imagedestroy($image);
}

function addOverlay($imagePath) {
    $logoPath = 'https://360annonces.com/assets/img/360annoncesL.png';

    // Load the images
    $image = imagecreatefromstring(file_get_contents($imagePath));
    $logo = imagecreatefrompng($logoPath);

    // Get dimensions
    list($imageWidth, $imageHeight) = getimagesize($imagePath);
    list($logoWidth, $logoHeight) = getimagesize($logoPath);

    // Resize the logo
    $newLogoWidth = $logoWidth;
    $newLogoHeight = $logoHeight;
    $logoResized = imagecreatetruecolor($newLogoWidth, $newLogoHeight);
    imagealphablending($logoResized, false);
    imagesavealpha($logoResized, true);
    imagecopyresampled($logoResized, $logo, 0, 0, 0, 0, $newLogoWidth, $newLogoHeight, $logoWidth, $logoHeight);

    // Calculate the position
    $x = ($imageWidth / 2) - ($newLogoWidth / 2);
    $y = ($imageHeight / 2) - ($newLogoHeight / 2);

    // Merge the logo onto the image
    imagecopy($image, $logoResized, $x, $y, 0, 0, $newLogoWidth, $newLogoHeight);

    // Save the image
    imagejpeg($image, $imagePath);

    // Free up memory
    imagedestroy($image);
    imagedestroy($logo);
    imagedestroy($logoResized);
}
?>
