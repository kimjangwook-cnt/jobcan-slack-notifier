<?php

namespace App\Services;

use ZipArchive;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class WebpConvertService
{
    private string $workDir;
    private array $conversionErrors = [];

    private string $uniqid;

    public function convertZipToWebp(UploadedFile $zipFile, int $quality): string
    {
        $this->uniqid = uniqid('webp_', true);
        // 一時作業ディレクトリを作成
        $this->workDir = storage_path('app/temp/' . $this->uniqid);
        if (!file_exists($this->workDir)) {
            mkdir($this->workDir, 0777, true);
        }

        try {
            // ZIPファイルを解凍
            $this->extractZip($zipFile);
            // 解凍した画像をWebP形式に変換
            $this->convertImagesToWebp($this->workDir, $quality);
            // 変換した画像を含むZIPファイルを作成
            $outputZip = $this->createOutputZip();

            return $outputZip;
        } finally {
            // 作業ディレクトリを削除
            $this->removeDirectory($this->workDir);
        }
    }

    public function convertOne(UploadedFile $imageFile, int $quality): string
    {
        $this->uniqid = uniqid('webp_', true);
        // 一時作業ディレクトリを作成
        $this->workDir = storage_path('app/temp/' . $this->uniqid);
        if (!file_exists($this->workDir)) {
            mkdir($this->workDir, 0777, true);
        }

        try {
            if ($imageFile->isimageFile() && !str_starts_with($imageFile->getBasename(), '._') && !str_starts_with($imageFile->getBasename(), '.')) {
                $extension = strtolower($imageFile->getExtension());
                // 対応している画像形式
                if (in_array($extension, ['jpg', 'jpeg', 'png', 'bmp', 'gif', 'ico', 'tiff', 'tif', 'heic', 'svg'])) {
                    $this->convertImageToWebp($imageFile, $extension, $quality);
                }
            }

            $newPath = $imageFile->getPath() . '/' . $imageFile->getBasename('.' . $extension) . '.webp';

            return $newPath;
        } finally {
            // 作業ディレクトリを削除
            $this->removeDirectory($this->workDir);
        }
    }

    private function extractZip(UploadedFile $zipFile): void
    {
        $zip = new ZipArchive;
        if ($zip->open($zipFile->path()) !== true) {
            throw new \RuntimeException('ZIPファイルの解凍に失敗しました。');
        }

        $zip->extractTo($this->workDir);
        $zip->close();
    }

    private function convertImagesToWebp(string $directory, int $quality): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );

        foreach ($files as $file) {
            if ($file->isFile() && !str_starts_with($file->getBasename(), '._') && !str_starts_with($file->getBasename(), '.')) {
                $extension = strtolower($file->getExtension());
                // 対応している画像形式
                if (in_array($extension, ['jpg', 'jpeg', 'png', 'bmp', 'gif', 'ico', 'tiff', 'tif', 'heic', 'svg'])) {
                    $this->convertImageToWebp($file, $extension, $quality);
                }
            }
        }
    }

    private function convertImageToWebp(\SplFileInfo $file, string $extension, int $quality): void
    {
        // 画像ファイルの検証を追加
        if (!$this->isValidImage($file->getPathname())) {
            $this->conversionErrors[] = str_replace(storage_path("app/temp/" . $this->uniqid) . '/', '', $file->getPathname()) . " - 無効な画像ファイル";
            return;
        }

        $image = $this->createImageResource($file, $extension);
        if (!$image) {
            $this->conversionErrors[] = str_replace(storage_path("app/temp/" . $this->uniqid) . '/', '', $file->getPathname()) . " - 画像リソースの作成に失敗";
            return;
        }

        // 트루컬러 이미지로 변환
        $width = imagesx($image);
        $height = imagesy($image);
        $trueColorImage = imagecreatetruecolor($width, $height);

        // 알파 채널 지원 설정
        imagealphablending($trueColorImage, false);
        imagesavealpha($trueColorImage, true);

        // 원본 이미지를 트루컬러 이미지에 복사
        imagecopy($trueColorImage, $image, 0, 0, 0, 0, $width, $height);
        imagedestroy($image);

        // WebP 형식으로 저장
        $newPath = $file->getPath() . '/' . $file->getBasename('.' . $extension) . '.webp';
        if (!imagewebp($trueColorImage, $newPath, $quality)) {
            Log::error("WebP形式への変換に失敗: " . $file->getPathname());
            $this->conversionErrors[] = str_replace(storage_path("app/temp/" . $this->uniqid) . '/', '', $file->getPathname()) . " - WebP形式への変換に失敗";
            imagedestroy($trueColorImage);
            return;
        }

        imagedestroy($trueColorImage);
        unlink($file->getPathname());
    }

    // 画像ファイルの検証メソッドを追加
    private function isValidImage(string $filepath): bool
    {
        if (!file_exists($filepath)) {
            return false;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($filepath);
        // 対応しているMIMEタイプ
        $validMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/bmp',
            'image/gif',
            'image/x-icon',
            'image/tiff',
            'image/heic',
            'image/svg+xml',
            'image/jpg'
        ];

        if (!in_array($mimeType, $validMimeTypes)) {
            return false;
        }

        // SVGファイルgetimagesizeチェックを除外
        if ($mimeType === 'image/svg+xml') {
            return true;
        }

        $imageInfo = @getimagesize($filepath);
        if ($imageInfo === false) {
            return false;
        }

        if ($imageInfo[0] <= 0 || $imageInfo[1] <= 0) {
            return false;
        }

        return true;
    }

    private function createImageResource(\SplFileInfo $file, string $extension)
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file->getPathname());

        try {
            switch ($mimeType) {
                case 'image/png':
                    return $this->createPngResource($file);
                case 'image/jpeg':
                case 'image/jpg':
                    return imagecreatefromjpeg($file->getPathname());
                case 'image/bmp':
                    return imagecreatefrombmp($file->getPathname());
                case 'image/gif':
                    return imagecreatefromgif($file->getPathname());
                case 'image/x-icon':
                    return imagecreatefromstring(file_get_contents($file->getPathname()));
                case 'image/tiff':
                    return $this->createTiffResource($file);
                case 'image/heic':
                    return $this->createHeicResource($file);
                case 'image/svg+xml':
                    return $this->createSvgResource($file);
                default:
                    Log::error("サポートされていない画像形式: {$mimeType}, ファイル: {$file->getPathname()}");
                    return null;
            }
        } catch (\Exception $e) {
            Log::error("画像リソースの作成に失敗: {$e->getMessage()}, ファイル: {$file->getPathname()}");
            return null;
        }
    }

    private function createPngResource(\SplFileInfo $file)
    {
        try {
            $image = @imagecreatefrompng($file->getPathname());
            if ($image === false) {
                Log::error("PNGファイルの作成に失敗: " . $file->getPathname());
                return null;
            }

            $width = imagesx($image);
            $height = imagesy($image);
            // TrueColorイメージを作成
            $newImage = imagecreatetruecolor($width, $height);

            if ($newImage === false) {
                Log::error("新規画像の作成に失敗: " . $file->getPathname());
                imagedestroy($image);
                return null;
            }

            // 透明度の設定
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            // 背景を透明に設定
            $transparent = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
            imagefilledrectangle($newImage, 0, 0, $width, $height, $transparent);
            // 画像をコピー
            imagealphablending($newImage, true);
            imagecopy($newImage, $image, 0, 0, 0, 0, $width, $height);
            imagedestroy($image);

            return $newImage;
        } catch (\Exception $e) {
            Log::error("PNG処理中にエラーが発生: " . $e->getMessage());
            return null;
        }
    }

    private function createOutputZip(): string
    {
        $outputZip = storage_path('app/public/webp_converted_' . date('ymdHi') . '.zip');


        // 変換エラーログファイルの作成
        if (!empty($this->conversionErrors)) {
            $logPath = $this->workDir . '/conversion_errors.txt';
            file_put_contents($logPath, implode("\n", $this->conversionErrors));
            // $zip->addFile($logPath, 'conversion_errors.txt');
        }

        $zip = new ZipArchive();
        if ($zip->open($outputZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('出力ZIPファイルの作成に失敗しました。');
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->workDir),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($this->workDir) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();

        return $outputZip;
    }

    private function removeDirectory(string $dir): bool
    {
        if (is_dir($dir)) {
            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                $path = $dir . '/' . $file;
                is_dir($path) ? $this->removeDirectory($path) : unlink($path);
            }
            return rmdir($dir);
        }
        return false;
    }

    // 追加画像形式を処理するためのメソッド
    private function createTiffResource(\SplFileInfo $file)
    {
        // Imagick拡張が必要です
        if (!extension_loaded('imagick')) {
            Log::error("Imagick拡張がインストールされていません。");
            return null;
        }

        try {
            $imagick = new \Imagick($file->getPathname());
            $imagick->setImageFormat('png');
            $tempFile = tempnam(sys_get_temp_dir(), 'tiff_');
            $imagick->writeImage($tempFile);
            $image = imagecreatefrompng($tempFile);
            unlink($tempFile);
            return $image;
        } catch (\Exception $e) {
            Log::error("TIFF処理中にエラーが発生: " . $e->getMessage());
            return null;
        }
    }

    private function createHeicResource(\SplFileInfo $file)
    {
        // Imagick拡張が必要です
        if (!extension_loaded('imagick')) {
            Log::error("Imagick拡張がインストールされていません。");
            return null;
        }

        try {
            $imagick = new \Imagick($file->getPathname());
            $imagick->setImageFormat('png');
            $tempFile = tempnam(sys_get_temp_dir(), 'heic_');
            $imagick->writeImage($tempFile);
            $image = imagecreatefrompng($tempFile);
            unlink($tempFile);
            return $image;
        } catch (\Exception $e) {
            Log::error("HEIC処理中にエラーが発生: " . $e->getMessage());
            return null;
        }
    }

    private function createSvgResource(\SplFileInfo $file)
    {
        // Imagick拡張が必要です
        if (!extension_loaded('imagick')) {
            Log::error("Imagick拡張がインストールされていません。");
            return null;
        }

        try {
            $imagick = new \Imagick($file->getPathname());
            $imagick->setImageFormat('png');
            $tempFile = tempnam(sys_get_temp_dir(), 'svg_');
            $imagick->writeImage($tempFile);
            $image = imagecreatefrompng($tempFile);
            unlink($tempFile);
            return $image;
        } catch (\Exception $e) {
            Log::error("SVG処理中にエラーが発生: " . $e->getMessage());
            return null;
        }
    }
}
