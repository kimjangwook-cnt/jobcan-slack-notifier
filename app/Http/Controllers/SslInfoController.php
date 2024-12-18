<?php

namespace App\Http\Controllers;

use App\Models\SslInfo;
use App\Services\SslCheckerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\WebpConvertService;

class SslInfoController extends Controller
{
    private SslCheckerService $sslCheckerService;

    public function __construct(SslCheckerService $sslCheckerService)
    {
        $this->sslCheckerService = $sslCheckerService;
    }

    public function list(Request $request)
    {
        $infoList = SslInfo::orderBy([
            'days_left' => 'asc',
            'company_name' => 'asc',
            'site_name' => 'asc',
        ])->get();
        $lastUpdatedAt = $infoList->first()->updated_at->timezone('Asia/Tokyo')->format('Y-m-d H:i:s');
        return response()->json([
            'success' => true,
            'message' => 'SSL証明書の確認に成功しました。',
            'data' => [
                'last_updated_at' => $lastUpdatedAt,
                'items' => $infoList,
            ],
            'error' => null,
        ]);
    }

    public function check(Request $request)
    {
        $domains = config('ssl_domain');
        try {
            $result = $this->sslCheckerService->checkCertificate($domains);
            return response()->json([
                'success' => true,
                'message' => 'SSL証明書の確認に成功しました。',
                'data' => $result,
                'error' => null,
            ]);
        } catch (\Exception $e) {
            Log::error('SSL証明書の確認中にエラーが発生: ' . $e->getMessage() . "\n" . json_encode(collect($e->getTrace())->map(function ($item) {
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

            // エラー発生時はエラーメッセージを返却
            return response()->json([
                'success' => false,
                'message' => 'SSL証明書の確認中にエラーが発生しました。kim.jangwook@connecty.co.jpへお知らせください。',
                'data' => null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
