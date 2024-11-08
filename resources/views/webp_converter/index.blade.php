@extends('layouts.app')

@section('title', 'WebP 変換')

@section('content')
<div class="bg-white rounded-lg shadow p-6">
    <h1 class="text-2xl font-bold mb-4">WebP 変換</h1>

    <h3 class="text-md font-bold mb-4">ZIPファイルをアップロードしてください</h3>

    <form action="{{ route('webp.convert') }}" method="POST" enctype="multipart/form-data"
        class="space-y-4" id="uploadForm">
        @csrf

        <input type="file" name="zip_file" id="zip_file" accept=".zip" required>

        @error('zip_file')
        <p class="text-red-500 text-sm">{{ $message }}</p>
        @enderror

        <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
            変換開始
        </button>
    </form>
</div>
@endsection