<?php

namespace App\Services;

use ZipArchive;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class WebpConvertService
{
    private string $workDir;
    private array $conversionErrors = [];

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
        // 画像ファイルの検証を追加
        if (!$this->isValidImage($file->getPathname())) {
            $this->conversionErrors[] = $file->getPathname() . " - 無効な画像ファイル";
            return;
        }

        $image = $this->createImageResource($file, $extension);
        if (!$image) {
            $this->conversionErrors[] = $file->getPathname() . " - 画像リソースの作成に失敗";
            return;
        }

        // WebP形式で保存
        $newPath = $file->getPath() . '/' . $file->getBasename('.' . $extension) . '.webp';
        if (!imagewebp($image, $newPath, $quality)) {
            $this->conversionErrors[] = $file->getPathname() . " - WebP形式への変換に失敗";
            imagedestroy($image);
            return;
        }

        imagedestroy($image);
        unlink($file->getPathname());
    }

    // 画像ファイルの検証メソッドを追加
    private function isValidImage(string $filepath): bool
    {
        if (!file_exists($filepath)) {
            return false;
        }

        // MIMEタイプの確認
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($filepath);
        $validMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/jpg'
        ];

        if (!in_array($mimeType, $validMimeTypes)) {
            return false;
        }

        // getimagesizeで画像情報を確認
        $imageInfo = @getimagesize($filepath);
        if ($imageInfo === false) {
            return false;
        }

        // 画像サイズが有効か確認（0より大きい必要がある）
        if ($imageInfo[0] <= 0 || $imageInfo[1] <= 0) {
            return false;
        }

        return true;
    }

    private function createImageResource(\SplFileInfo $file, string $extension)
    {
        // MIMEタイプを確認して実際の画像形式を判断
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file->getPathname());

        try {
            switch ($mimeType) {
                case 'image/png':
                    return $this->createPngResource($file);
                case 'image/jpeg':
                case 'image/jpg':
                    return imagecreatefromjpeg($file->getPathname());
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
                Log::error("PNG 파일 생성 실패: " . $file->getPathname());
                return null;
            }

            $width = imagesx($image);
            $height = imagesy($image);
            $newImage = imagecreatetruecolor($width, $height);

            if ($newImage === false) {
                Log::error("새 이미지 생성 실패: " . $file->getPathname());
                imagedestroy($image);
                return null;
            }

            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            imagecopy($newImage, $image, 0, 0, 0, 0, $width, $height);
            imagedestroy($image);

            return $newImage;
        } catch (\Exception $e) {
            Log::error("PNG 처리 중 에러 발생: " . $e->getMessage());
            return null;
        }
    }

    private function createOutputZip(UploadedFile $originalZip): string
    {
        $outputZip = storage_path('app/public/converted_' . $originalZip->getClientOriginalName());

        // 変換 エラー ログ ファイルの作成
        if (!empty($this->conversionErrors)) {
            $logPath = $this->workDir . '/conversion_errors.txt';
            file_put_contents($logPath, implode("\n", $this->conversionErrors));
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
}
