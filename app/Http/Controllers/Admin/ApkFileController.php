<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApkFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ApkFileController extends Controller
{
    /**
     * Display APK management page
     */
    public function index()
    {
        $apkFiles = ApkFile::with('uploader')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        $activeApk = ApkFile::where('is_active', true)->first();
        
        return view('admin.pages.apk-files', compact('apkFiles', 'activeApk'));
    }

    /**
     * Store uploaded APK file
     */
    public function store(Request $request)
    {
        $request->validate([
            'apk_file' => 'required|file|mimes:apk|max:102400', // Max 100MB
            'version' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            // Deactivate all existing APKs
            ApkFile::where('is_active', true)->update(['is_active' => false]);

            // Handle file upload
            $file = $request->file('apk_file');
            $originalName = $file->getClientOriginalName();
            $fileName = 'apk_' . time() . '_' . uniqid() . '.apk';
            $filePath = $file->storeAs('apk', $fileName, 'public');

            // Create APK record
            $apkFile = ApkFile::create([
                'file_name' => $fileName,
                'file_path' => $filePath,
                'original_name' => $originalName,
                'version' => $request->version,
                'file_size' => $file->getSize(),
                'is_active' => true,
                'description' => $request->description,
                'uploaded_by' => Auth::id(),
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'APK file uploaded successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to upload APK: ' . $e->getMessage());
        }
    }

    /**
     * Set active APK
     */
    public function setActive($id)
    {
        try {
            DB::beginTransaction();
            
            // Deactivate all
            ApkFile::where('is_active', true)->update(['is_active' => false]);
            
            // Activate selected
            $apkFile = ApkFile::findOrFail($id);
            $apkFile->update(['is_active' => true]);
            
            DB::commit();
            return redirect()->back()->with('success', 'APK set as active successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to set active APK: ' . $e->getMessage());
        }
    }

    /**
     * Delete APK file
     */
    public function destroy($id)
    {
        try {
            $apkFile = ApkFile::findOrFail($id);
            
            // Don't allow deleting active APK
            if ($apkFile->is_active) {
                return redirect()->back()->with('error', 'Cannot delete active APK. Please set another APK as active first.');
            }
            
            // Delete file from storage
            if (Storage::disk('public')->exists($apkFile->file_path)) {
                Storage::disk('public')->delete($apkFile->file_path);
            }
            
            $apkFile->delete();
            
            return redirect()->back()->with('success', 'APK file deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete APK: ' . $e->getMessage());
        }
    }
}
