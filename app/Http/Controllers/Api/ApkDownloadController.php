<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApkFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ApkDownloadController extends Controller
{
    /**
     * Get active APK info (public endpoint)
     */
    public function info()
    {
        $apk = ApkFile::where('is_active', true)->first();
        
        if (!$apk) {
            return response()->json([
                'success' => false,
                'message' => 'No APK file available',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $apk->id,
                'version' => $apk->version,
                'file_name' => $apk->original_name,
                'file_size' => $apk->file_size,
                'formatted_size' => $apk->formatted_size,
                'description' => $apk->description,
                'uploaded_at' => $apk->created_at->format('Y-m-d H:i:s'),
                'download_url' => url('/api/apk/download'),
            ],
        ]);
    }

    /**
     * Download APK file (public endpoint)
     */
    public function download(): BinaryFileResponse
    {
        $apk = ApkFile::where('is_active', true)->first();
        
        if (!$apk) {
            abort(404, 'APK file not found');
        }

        $filePath = storage_path('app/public/' . $apk->file_path);
        
        if (!file_exists($filePath)) {
            abort(404, 'APK file not found on server');
        }

        return response()->download($filePath, $apk->original_name, [
            'Content-Type' => 'application/vnd.android.package-archive',
        ]);
    }
}
