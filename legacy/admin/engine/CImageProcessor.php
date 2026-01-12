<?php

class ImageProcessor {
    private $inputFile;
    private $outputPath;
    private $width;
    private $height;

    public function __construct($inputFile, $outputPath, $width, $height) {
        $this->inputFile = $inputFile;
        $this->outputPath = $outputPath;
        $this->width = $width;
        $this->height = $height;
    }

    public function processImage($crop = false) {
        $extension = pathinfo($this->inputFile['name'], PATHINFO_EXTENSION);

        if ($extension === 'svg') {
            // Если изображение в формате SVG, копируем его без изменений
            move_uploaded_file($this->inputFile['tmp_name'], $this->outputPath);
        } else {
            // Загружаем изображение с поддержкой разных форматов
            $image = imagecreatefromstring(file_get_contents($this->inputFile['tmp_name']));

            // Получаем исходные размеры изображения
            $originalWidth = imagesx($image);
            $originalHeight = imagesy($image);

            // Рассчитываем новые размеры с сохранением пропорций
            $newWidth = $this->width;
            $newHeight = ($originalHeight / $originalWidth) * $newWidth;

            if ($newHeight > $this->height) {
                $newHeight = $this->height;
                $newWidth = ($originalWidth / $originalHeight) * $newHeight;
            }

            // Создаем прозрачную подложку
            $outputImage = imagecreatetruecolor($this->width, $this->height);

            // Переводим прозрачность изображения в формат WebP
            $outputImage = $this->convertToWebP($outputImage);

            // Рассчитываем координаты для центрирования
            $x = ($this->width - $newWidth) / 2;
            $y = ($this->height - $newHeight) / 2;

            // Масштабируем изображение и размещаем его по центру
            imagecopyresampled($outputImage, $image, $x, $y, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

            // Сохраняем изображение в формате WebP
            imagewebp($outputImage, $this->outputPath);

            // Очищаем память
            imagedestroy($image);
            imagedestroy($outputImage);
        }
    }

    public function cropImage($cropWidth, $cropHeight) {
        $extension = pathinfo($this->inputFile['name'], PATHINFO_EXTENSION);

        if ($extension === 'svg') {
            // Если изображение в формате SVG, копируем его без изменений
            copy($this->inputFile['tmp_name'], $this->outputPath);
        } else {
            // Загружаем изображение
            $image = imagecreatefromstring(file_get_contents($this->inputFile['tmp_name']));

            // Вырезаем кусок из изображения нужного размера
            $croppedImage = imagecrop($image, [
                'x' => (imagesx($image) - $cropWidth) / 2,
                'y' => (imagesy($image) - $cropHeight) / 2,
                'width' => $cropWidth,
                'height' => $cropHeight
            ]);

            // Создаем прозрачную подложку
            $outputImage = imagecreatetruecolor($cropWidth, $cropHeight);
            imagealphablending($outputImage, false);
            imagesavealpha($outputImage, true);
            $transparent = imagecolorallocatealpha($outputImage, 0, 0, 0, 127);
            imagefill($outputImage, 0, 0, $transparent);

            // Размещаем вырезанный кусок по центру
            $x = ($cropWidth - $cropWidth) / 2;
            $y = ($cropHeight - $cropHeight) / 2;
            imagecopy($outputImage, $croppedImage, $x, $y, 0, 0, $cropWidth, $cropHeight);

            // Сохраняем изображение в формате WebP
            // Сохраняем изображение в формате WebP или JPEG
            if (function_exists('imagewebp')) {
                $extension = '.webp';
                imagewebp($outputImage, $this->outputPath, 90);
            } else {
                // Fallback на JPEG если WebP недоступен
                $extension = '.jpg';
                $jpegPath = str_replace('.webp', $extension, $this->outputPath);
                imagejpeg($outputImage, $jpegPath, 90);
            }

            // Очищаем память
            imagedestroy($image);
            imagedestroy($croppedImage);
            imagedestroy($outputImage);

            return $extension;
        }
    }

    private function convertToWebP($image) {
        $outputImage = imagecreatetruecolor($this->width, $this->height);
        imagealphablending($outputImage, false);
        imagesavealpha($outputImage, true);
        $transparent = imagecolorallocatealpha($outputImage, 0, 0, 0, 127);
        imagefill($outputImage, 0, 0, $transparent);
        return $outputImage;
    }
}
?>
