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
        $request->validate([
            'zip_file' => 'required|file|mimes:zip|max:51200',
            'quality' => 'required|integer|min:0|max:100',
        ]);

        try {
            $outputZip = $this->webpConvertService->convertZipToWebp($request->file('zip_file'), $request->quality);
            Log::info('変換が成功しました: ' . $outputZip);

            return response()->download($outputZip)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('処理中にエラーが発生しました: ' . $e->getMessage());
            return back()->with('error', '処理中にエラーが発生しました: ' . $e->getMessage());
        }
    }
}
