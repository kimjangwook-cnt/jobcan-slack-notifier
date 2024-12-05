<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\WebpConvertService;

class WebpConverterController extends Controller
{
    private WebpConvertService $webpConvertService;

    public function __construct(WebpConvertService $webpConvertService)
    {
        $this->webpConvertService = $webpConvertService;
    }

    public function index()
    {
        return view('webp_converter.index');
    }

    public function convert(Request $request)
    {
        Log::info('リクエスト: ' . json_encode($request->all()));
        $max = 1024 * 1024 * 1024; // 1GB

        $request->validate([
            'zip_file' => 'required|file|mimes:zip|max:' . $max,
            'quality' => 'required|integer|min:0|max:100',
        ], [
            'zip_file.required' => 'ZIPファイルを選択してください。',
            'zip_file.file' => 'アップロードされたファイルが無効です。',
            'zip_file.mimes' => 'ZIPファイル形式のみアップロード可能です。',
            'zip_file.max' => 'ファイルサイズは1GB以下にしてください。',
            'quality.required' => '圧縮率を指定してください。',
            'quality.integer' => '圧縮率は整数で指定してください。',
            'quality.min' => '圧縮率は0以上で指定してください。',
            'quality.max' => '圧縮率は100以下で指定してください。',
        ]);

        try {
            $outputZip = $this->webpConvertService->convertZipToWebp($request->file('zip_file'), $request->quality);
            Log::info('変換が成功しました: ' . $outputZip);

            return response()->json([
                'success' => true,
                'message' => '変換が成功しました',
                'data' => [
                    'output_zip' => base64_encode(file_get_contents($outputZip)),
                    'file_name' => basename($outputZip),
                ],
                'error' => null,
            ]);
        } catch (\Exception $e) {
            Log::error('処理中にエラーが発生しました: ' . $e->getMessage() . "\n" . json_encode(collect($e->getTrace())->map(function ($item) {
                if (isset($item['class'])) {
                    return 'class: ' . "(line => " . ($item['line'] ?? '?') . ")" . $item['class'];
                }
                if (isset($item['function'])) {
                    return 'function: ' . "(line => " . ($item['line'] ?? '?') . ")" . $item['function'];
                }
                if (isset($item['file'])) {
                    return 'file: ' . "(line => " . ($item['line'] ?? '?') . ")" . $item['file'];
                }
                return '?';
            })->toArray(), JSON_PRETTY_PRINT));

            return response()->json([
                'success' => false,
                'message' => '処理中にエラーが発生しました。kim.jangwook@connecty.co.jpへお知らせください。',
                'data' => null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function convertOne(Request $request)
    {
        Log::info('リクエスト: ' . json_encode($request->all()));
        $max = 1024 * 1024 * 1024; // 1GB

        $request->validate([
            'image_file' => 'required|file|mimes:jpg,jpeg,png,bmp,gif,ico,tiff,tif,heic,svg|max:' . $max,
            'quality' => 'required|integer|min:0|max:100',
        ], [
            'image_file.required' => '画像ファイルを選択してください。',
            'image_file.file' => 'アップロードされたファイルが無効です。',
            'image_file.mimes' => '画像ファイル形式のみアップロード可能です。',
            'image_file.max' => 'ファイルサイズは1GB以下にしてください。',
            'quality.required' => '圧縮率を指定してください。',
            'quality.integer' => '圧縮率は整数で指定してください。',
            'quality.min' => '圧縮率は0以上で指定してください。',
            'quality.max' => '圧縮率は100以下で指定してください。',
        ]);

        try {
            $outputFile = $this->webpConvertService->convertOne($request->file('image_file'), $request->quality);

            if (!$outputFile) {
                throw new \Exception('変換に失敗しました。');
            }

            Log::info('変換が成功しました: ' . $outputFile);

            return response()->json([
                'success' => true,
                'message' => '変換が成功しました',
                'data' => [
                    'output_file' => base64_encode(file_get_contents($outputFile)),
                    'file_name' => basename($outputFile),
                ],
                'error' => null,
            ]);
        } catch (\Exception $e) {
            Log::error('処理中にエラーが発生しました: ' . $e->getMessage() . "\n" . json_encode(collect($e->getTrace())->map(function ($item) {
                if (isset($item['class'])) {
                    return 'class: ' . "(line => " . ($item['line'] ?? '?') . ")" . $item['class'];
                }
                if (isset($item['function'])) {
                    return 'function: ' . "(line => " . ($item['line'] ?? '?') . ")" . $item['function'];
                }
                if (isset($item['file'])) {
                    return 'file: ' . "(line => " . ($item['line'] ?? '?') . ")" . $item['file'];
                }
                return '?';
            })->toArray(), JSON_PRETTY_PRINT));

            return response()->json([
                'success' => false,
                'message' => '処理中にエラーが発生しました。kim.jangwook@connecty.co.jpへお知らせください。',
                'data' => null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
