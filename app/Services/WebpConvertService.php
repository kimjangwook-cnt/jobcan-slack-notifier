<?php

namespace App\Services;

use ZipArchive;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

/**
 * WebP画像変換サービスクラス
 *
 * 画像ファイルをWebP形式に変換するためのサービスクラスです。
 * ZIP形式の一括変換と単一ファイルの変換に対応しています。
 */
class WebpConvertService
{
    /**
     * 作業用一時ディレクトリのパス
     */
    private string $workDir;

    /**
     * 変換処理中に発生したエラーを格納する配列
     */
    private array $conversionErrors = [];

    /**
     * 一時ファイル用のユニークID
     */
    private string $uniqid;

    /**
     * ZIPファイル内の画像をWebP形式に一括変換
     *
     * @param UploadedFile $zipFile アップロードされたZIPファイル
     * @param int $quality 変換後の画質(0-100)
     * @return string 変換後のZIPファイルパス
     */
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

    /**
     * 単一の画像ファイルをWebP形式に変換
     *
     * @param UploadedFile $imageFile アップロードされた画像ファイル
     * @param int $quality 変換後の画質(0-100)
     * @return string|null 変換後のファイルパス、失敗時はnull
     */
    public function convertOne(UploadedFile $imageFile, int $quality): string | null
    {
        $this->uniqid = uniqid('webp_', true);
        $this->workDir = storage_path('app/temp/' . $this->uniqid);
        if (!file_exists($this->workDir)) {
            mkdir($this->workDir, 0777, true);
        }

        try {
            $extension = strtolower($imageFile->getClientOriginalExtension());

            // サポートされている画像形式かどうかを確認
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'bmp', 'gif', 'ico', 'tiff', 'tif', 'heic', 'svg'])) {
                Log::error('サポートされていないファイル形式です: ' . $extension);
                return null;
            }

            // 画像をWebPに変換してパスを返す
            $outputPath = $this->convertImageToWebp($imageFile, $extension, $quality);

            if ($outputPath) {
                Log::info('変換成功: ' . $outputPath);
                return $outputPath;
            }

            Log::error('変換失敗: ' . $imageFile->getClientOriginalName());
            return null;
        } finally {
            // 作業ディレクトリのクリーンアップ
            $this->removeDirectory($this->workDir);
        }
    }

    /**
     * ZIPファイルを解凍する
     *
     * @param UploadedFile $zipFile 解凍対象のZIPファイル
     * @throws \RuntimeException ZIPファイルの解凍に失敗した場合
     */
    private function extractZip(UploadedFile $zipFile): void
    {
        $zip = new ZipArchive;
        if ($zip->open($zipFile->path()) !== true) {
            throw new \RuntimeException('ZIPファイルの解凍に失敗しました。');
        }

        $zip->extractTo($this->workDir);
        $zip->close();
    }

    /**
     * 指定ディレクトリ内の画像をWebP形式に変換
     *
     * @param string $directory 変換対象の画像が格納されているディレクトリパス
     * @param int $quality 変換後の画質(0-100)
     */
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

    /**
     * 個別の画像ファイルをWebP形式に変換
     *
     * @param \SplFileInfo $file 変換対象の画像ファイル
     * @param string $extension ファイルの拡張子
     * @param int $quality 変換後の画質(0-100)
     * @return string|null 変換後のファイルパス、失敗時はnull
     */
    private function convertImageToWebp(\SplFileInfo $file, string $extension, int $quality): string | null
    {
        // 画像ファイルの検証を追加
        if (!$this->isValidImage($file->getPathname())) {
            $this->conversionErrors[] = str_replace(storage_path("app/temp/" . $this->uniqid) . '/', '', $file->getPathname()) . " - 無効な画像ファイル";
            return null;
        }

        $image = $this->createImageResource($file, $extension);
        if (!$image) {
            $this->conversionErrors[] = str_replace(storage_path("app/temp/" . $this->uniqid) . '/', '', $file->getPathname()) . " - 画像リソースの作成に失敗";
            return null;
        }

        // トゥルーカラー画像に変換
        $width = imagesx($image);
        $height = imagesy($image);
        $trueColorImage = imagecreatetruecolor($width, $height);

        // アルファチャンネルサポートの設定
        imagealphablending($trueColorImage, false);
        imagesavealpha($trueColorImage, true);

        // 元の画像をトゥルーカラー画像にコピー
        imagecopy($trueColorImage, $image, 0, 0, 0, 0, $width, $height);
        imagedestroy($image);

        // WebP形式で保存
        $newPath = $file->getPath() . '/' . $file->getBasename('.' . $extension) . '.webp';
        if (!imagewebp($trueColorImage, $newPath, $quality)) {
            Log::error("WebP形式への変換に失敗: " . $file->getPathname());
            $this->conversionErrors[] = str_replace(storage_path("app/temp/" . $this->uniqid) . '/', '', $file->getPathname()) . " - WebP形式への変換に失敗";
            imagedestroy($trueColorImage);
            return null;
        }

        imagedestroy($trueColorImage);
        unlink($file->getPathname());

        return $newPath;
    }

    /**
     * 画像ファイルの有効性を検証
     *
     * @param string $filepath 検証対象の画像ファイルパス
     * @return bool 有効な画像の場合はtrue
     */
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

    /**
     * 画像リソースを作成
     *
     * @param \SplFileInfo $file 画像ファイル
     * @param string $extension ファイルの拡張子
     * @return resource|null 画像リソース、失敗時はnull
     */
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

    /**
     * PNG画像リソースを作成
     *
     * @param \SplFileInfo $file PNGファイル
     * @return resource|null 画像リソース、失敗時はnull
     */
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

    /**
     * 変換後のZIPファイルを作成
     *
     * @return string 作成されたZIPファイルのパス
     * @throws \RuntimeException ZIPファイルの作成に失敗した場合
     */
    private function createOutputZip(): string
    {
        $outputZip = storage_path('app/public/webp_converted_' . date('ymdHi') . '.zip');

        // 変換エラーログファイルの作成
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

    /**
     * ディレクトリとその中身を再帰的に削除
     *
     * @param string $dir 削除対象のディレクトリパス
     * @return bool 削除成功時はtrue
     */
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

    /**
     * TIFF画像をPNG形式に変換してリソースを作成
     *
     * @param \SplFileInfo $file TIFFファイル
     * @return resource|null 画像リソース、失敗時はnull
     */
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

    /**
     * HEIC画像をPNG形式に変換してリソースを作成
     *
     * @param \SplFileInfo $file HEICファイル
     * @return resource|null 画像リソース、失敗時はnull
     */
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

    /**
     * SVG画像をPNG形式に変換してリソースを作成
     *
     * @param \SplFileInfo $file SVGファイル
     * @return resource|null 画像リソース、失敗時はnull
     */
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
