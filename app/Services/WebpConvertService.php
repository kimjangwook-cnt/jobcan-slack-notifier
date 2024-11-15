<?php

namespace App\Services;

use ZipArchive;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class WebpConvertService
{
    private string $workDir;

    public function convertZipToWebp(UploadedFile $zipFile, int $quality): string
    {
        // 一時作業ディレクトリを作成
        $this->workDir = storage_path('app/temp/' . uniqid('webp_', true));
        if (!file_exists($this->workDir)) {
            mkdir($this->workDir, 0777, true);
        }

        try {
            // ZIPファイルを解凍
            $this->extractZip($zipFile);
            // 解凍した画像をWebP形式に変換
            $this->convertImagesToWebp($this->workDir, $quality);
            // 変換した画像を含むZIPファイルを作成
            $outputZip = $this->createOutputZip($zipFile);

            return $outputZip;
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
        // 指定されたディレクトリ内の画像ファイルを再帰的に取得
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );

        foreach ($files as $file) {
            if ($file->isFile() && !str_starts_with($file->getBasename(), '._') && !str_starts_with($file->getBasename(), '.')) {
                $extension = strtolower($file->getExtension());
                // 対応する画像形式をWebPに変換
                if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                    $this->convertImageToWebp($file, $extension, $quality);
                }
            }
        }
    }

    private function convertImageToWebp(\SplFileInfo $file, string $extension, int $quality): void
    {
        $image = $this->createImageResource($file, $extension);
        if (!$image) return;

        // WebP形式で保存
        $newPath = $file->getPath() . '/' . $file->getBasename('.' . $extension) . '.webp';
        imagewebp($image, $newPath, $quality);
        imagedestroy($image);

        // 元のファイルを削除
        unlink($file->getPathname());
    }

    private function createImageResource(\SplFileInfo $file, string $extension)
    {
        // 画像リソースを作成
        if ($extension === 'png') {
            return $this->createPngResource($file);
        }
        return imagecreatefromjpeg($file->getPathname());
    }

    private function createPngResource(\SplFileInfo $file)
    {
        $image = imagecreatefrompng($file->getPathname());
        $width = imagesx($image);
        $height = imagesy($image);
        $newImage = imagecreatetruecolor($width, $height);

        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        imagecopy($newImage, $image, 0, 0, 0, 0, $width, $height);
        imagedestroy($image);

        return $newImage;
    }

    private function createOutputZip(UploadedFile $originalZip): string
    {
        $outputZip = storage_path('app/public/converted_' . $originalZip->getClientOriginalName());

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
}
