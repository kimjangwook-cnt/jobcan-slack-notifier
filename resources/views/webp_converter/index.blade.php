@extends('layouts.app')

@section('title', 'WebP 変換')

@section('content')
<div class="bg-white rounded-lg shadow p-6">
    <h1 class="text-2xl font-bold mb-4">WebP 変換</h1>

    <h3 class="text-md font-bold mb-4">ZIPファイルをアップロードしてください</h3>

    <form action="{{ route('webp.convert') }}" method="POST" enctype="multipart/form-data"
        class="space-y-4" id="uploadForm">
        @csrf

        <div class="relative">
            <input type="file" name="zip_file" id="zip_file" accept=".zip" required
                class="hidden" onchange="updateFileName(this)">
            <label for="zip_file"
                class="flex items-center justify-center w-full px-4 py-2 border border-gray-300 rounded-lg cursor-pointer bg-white hover:bg-gray-50 transition-colors">
                <svg class="w-6 h-6 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                <span id="fileNameDisplay" class="text-gray-600">ZIPファイルを選択してください</span>
            </label>
        </div>

        <script>
            function updateFileName(input) {
                const fileNameDisplay = document.getElementById('fileNameDisplay');
                fileNameDisplay.textContent = input.files[0] ? input.files[0].name : 'ZIPファイルを選択してください';
            }
        </script>

        <div class="mt-4">
            <label for="quality" class="block text-sm font-medium text-gray-700">圧縮率（画質）</label>
            <div class="flex items-center gap-2">
                <input type="range" name="quality" id="quality" min="0" max="100" value="90"
                    class="w-full" oninput="qualityValue.value = this.value">
                <output id="qualityValue" class="text-sm">90</output>
            </div>
        </div>

        @error('zip_file')
        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
        @enderror

        @error('quality')
        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
        @enderror

        <button type="submit" id="submitButton"
            onclick="return handleSubmit(event)"
            class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
            変換開始
        </button>

        <!-- 処理中の表示用オーバーレイ -->
        <div id="loadingOverlay" class="hidden fixed inset-0 bg-black bg-opacity-10 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-lg text-center">
                <p class="text-lg font-semibold">処理中です...</p>
                <p class="text-sm text-gray-600">しばらくお待ちください</p>
            </div>
        </div>

        <script>
            function handleSubmit(event) {
                event.preventDefault(); // フォームのデフォルトの送信を防ぐ
                document.getElementById('loadingOverlay').classList.remove('hidden');
                // document.getElementById('submitButton').disabled = true;
                // document.getElementById('zip_file').disabled = true;
                // document.getElementById('quality').disabled = true;

                // フォームを送信
                const form = document.getElementById('uploadForm');
                const formData = new FormData(form);

                fetch(form.action, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('変換に失敗しました');
                        }
                        return response.blob();
                    })
                    .then(blob => {
                        // ダウンロードリンクを作成して自動クリック
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = 'converted_images.zip';
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                    })
                    .catch(error => {
                        console.error('エラー:', error);
                        alert('処理中にエラーが発生しました。時間をおいて再度お試しください。');
                    })
                    .finally(() => {
                        document.getElementById('uploadForm').reset();
                        document.getElementById('fileNameDisplay').textContent = 'ZIPファイルを選択してください';
                        document.getElementById('qualityValue').textContent = 90;
                        document.getElementById('loadingOverlay').classList.add('hidden');
                    });
            }
        </script>

        @if (session('error'))
        <div class="my-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded text-sm">
            {{ session('error') }}
        </div>
        @endif
    </form>
</div>

<div class="mt-4 bg-white rounded-lg shadow-md p-6 border border-gray-200">
    <h3 class="text-lg font-medium text-gray-900 mb-4">使い方</h3>
    <div class="space-y-3 mb-6">
        <div class="flex items-start">
            <span class="text-gray-600 text-sm">1.</span>
            <p class="ml-2 text-sm text-gray-600">変換したい画像ファイルをZIPファイルにまとめます。</p>
        </div>
        <div class="flex items-start">
            <span class="text-gray-600 text-sm">2.</span>
            <p class="ml-2 text-sm text-gray-600">「ファイルを選択」ボタンをクリックし、作成したZIPファイルを選択します。</p>
        </div>
        <div class="flex items-start">
            <span class="text-gray-600 text-sm">3.</span>
            <p class="ml-2 text-sm text-gray-600">画質（圧縮率）を0〜100の間で設定します。数値が大きいほど高画質になります。</p>
        </div>
        <div class="flex items-start">
            <span class="text-gray-600 text-sm">4.</span>
            <p class="ml-2 text-sm text-gray-600">「変換開始」ボタンをクリックすると、WebP形式に変換されたファイルがZIPファイルとしてダウンロードされます。</p>
        </div>
    </div>

    <h3 class="text-lg font-medium text-gray-900 mb-4 mt-8">※ 注意事項</h3>
    <div class="space-y-3">
        <div class="flex items-start">
            <span class="text-gray-600 text-sm">・</span>
            <p class="ml-2 text-sm text-gray-600">ZIPファイルは1GBまでアップロード可能ですが、安定した動作のため100MB以下でのご利用を推奨しております。</p>
        </div>
        <div class="flex items-start">
            <span class="text-gray-600 text-sm">・</span>
            <p class="ml-2 text-sm text-gray-600">SVGなど変換に対応していないファイル形式は、そのまま出力ZIPファイルに格納されます。</p>
        </div>
        <div class="flex items-start">
            <span class="text-gray-600 text-sm">・</span>
            <p class="ml-2 text-sm text-gray-600">正常に動作しない場合は、kim.jangwook@connecty.co.jpまでご連絡ください。</p>
        </div>
    </div>
</div>
@endsection