<?php

namespace App\Http\Controllers;

use App\Models\DomainInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DomainInfoController extends Controller
{
    public function list(Request $request)
    {
        $infoList = DomainInfo::orderBy('days_left', 'asc')
            ->orderBy('company_name', 'asc')
            ->get();
        return response()->json([
            'success' => true,
            'message' => 'ドメイン情報の確認に成功しました。',
            'data' => [
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
