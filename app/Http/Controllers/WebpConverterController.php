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

            return response()->download($outputZip)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('処理中にエラーが発生しました: ' . $e->getMessage());
            return back()->withErrors(['error' => '処理中にエラーが発生しました。時間をおいて再度お試しください。']);
        }
    }
}
