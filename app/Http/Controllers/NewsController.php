<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        // Hindari mengambil kolom 'content' dan 'attachments' yang berukuran sangat besar jika diminta
        // agar tidak terjadi Memory Exhausted (Error 500) saat memuat banyak baris di Manage News (All Data / 1000 baris)
        $columns = [
            'id', 'title', 'keyword', 'category', 'target_unit', 
            'headline', 'created_by', 'updated_by', 'created_at', 'updated_at', 'end_time',
            'attachment', 'attachments'
        ];
        
        if (!$request->has('exclude_content') && !$request->has('only_first_image')) {
            $columns[] = 'content';
        } elseif ($request->has('only_first_image')) {
            $columns[] = 'content';
        }
        
        $query = \App\Models\News::select($columns);

        // Optional filtering
        // Generic Search (Title OR Keyword OR Attachments, and optionally Content)
        if ($request->search && trim($request->search) !== '') {
            $term = strtolower(trim($request->search));
            $term = preg_replace('/[\s\r\n]+/', '%', $term);
            $includeContent = $request->boolean('include_content', false);
            
            $query->where(function ($q) use ($term, $includeContent) {
                $q->where(DB::raw('LOWER(title)'), 'like', '%' . $term . '%')
                    ->orWhere(DB::raw('LOWER(keyword)'), 'like', '%' . $term . '%')
                    ->orWhere(DB::raw('LOWER(attachment)'), 'like', '%' . $term . '%')
                    ->orWhere(DB::raw('LOWER(attachments)'), 'like', '%' . $term . '%');
                
                // Only scan full HTML text if explicitly requested, as full-table LIKE scans across 8000+ long HTML rows cause PostgreSQL 30s timeouts
                if ($includeContent) {
                    $q->orWhere(DB::raw('LOWER(content)'), 'like', '%' . $term . '%');
                }
            });
        }

        // Filter by specific Title column
        if ($request->title && trim($request->title) !== '') {
            $term = strtolower(trim($request->title));
            // Replace all whitespace (spaces, tabs, newlines) with % for flexible matching
            $term = preg_replace('/[\s\r\n]+/', '%', $term);
            $query->where(DB::raw('LOWER(title)'), 'like', '%' . $term . '%');
        }

        // Filter by specific Keyword column
        if ($request->keyword && trim($request->keyword) !== '') {
            $term = strtolower(trim($request->keyword));
            $term = preg_replace('/[\s\r\n]+/', '%', $term);
            $query->where(DB::raw('LOWER(keyword)'), 'like', '%' . $term . '%');
        }

        // Category filter
        if ($request->categories) {
            $cats = is_array($request->categories) ? $request->categories : json_decode($request->categories, true);
            if (!empty($cats) && is_array($cats)) {
                $query->where(function ($q) use ($cats) {
                    foreach ($cats as $c) {
                        $c = trim($c);
                        if ($c !== '') {
                            $q->orWhere('category', 'like', '%' . $c . '%');
                        }
                    }
                });
            }
        } elseif ($request->category && trim($request->category) !== '') {
            $cat = trim($request->category);
            // Use LIKE for flexibility against "Promo & Program" vs "Promo & Program " vs "Promo"
            $query->where('category', 'like', '%' . $cat . '%');
        }

        // Filter by specific unit dashboard if needed (e.g. only show News for 'Banking')
        if ($request->unit) {
            $query->where(function ($q) use ($request) {
                $unit = trim($request->unit);
                // 1. Exact match / JSON match / Comma-separated match for the requested unit
                $q->where('target_unit', 'like', '%"' . $unit . '"%')
                    ->orWhere('target_unit', 'like', '%' . $unit . '%')
                    ->orWhere('target_unit', $unit)

                    // 2. Always include "All Layanan" (Global)
                    ->orWhere('target_unit', 'like', '%"All Layanan"%')
                    ->orWhere('target_unit', 'like', '%All Layanan%')
                    ->orWhere('target_unit', 'All Layanan');

                // 3. Include NULL or empty string (Visible to all)
                $q->orWhereNull('target_unit')
                  ->orWhere('target_unit', '');
            });
        }

        // Headline filter
        if ($request->headline) {
            $query->where('headline', $request->headline);
        }

        // Created Name filter
        if ($request->created_name && trim($request->created_name) !== '') {
            $term = strtolower(trim($request->created_name));
            $query->where(DB::raw('LOWER(created_by)'), 'like', '%' . $term . '%');
        }

        // Updated Name filter
        if ($request->updated_name && trim($request->updated_name) !== '') {
            $term = strtolower(trim($request->updated_name));
            $query->where(DB::raw('LOWER(updated_by)'), 'like', '%' . $term . '%');
        }

        // Status (EndTime) filter
        if ($request->status && trim($request->status) !== '') {
            $status = trim($request->status);
            $today = now()->format('Y-m-d');
            if ($status === 'Aktif') {
                $query->where(function ($q) use ($today) {
                    $q->whereNull('end_time')
                      ->orWhereDate('end_time', '>=', $today);
                });
            } elseif ($status === 'Expired') {
                $query->whereNotNull('end_time')
                      ->whereDate('end_time', '<', $today);
            }
        }

        // Sorting
        $sortColumn = $request->sort_column;
        $sortOrder = $request->sort_order;

        // Auto-detect sorting for Promo & Program to closest end_time if not explicitly specified
        if (!$sortColumn) {
            $isPromo = false;
            if ($request->category && stripos($request->category, 'Promo') !== false) {
                $isPromo = true;
            } elseif ($request->categories) {
                $catsStr = is_array($request->categories) ? json_encode($request->categories) : $request->categories;
                if (stripos($catsStr, 'Promo') !== false) {
                    $isPromo = true;
                }
            }

            if ($isPromo) {
                $sortColumn = 'end_time';
                $sortOrder = 'asc';
            } else {
                $sortColumn = 'created_at';
                $sortOrder = 'desc';
            }
        }

        $order = strtolower($sortOrder ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

        if ($sortColumn === 'end_time') {
            $query->orderByRaw("CASE WHEN end_time IS NULL THEN 1 ELSE 0 END ASC, end_time {$order}");
        } elseif ($sortColumn === 'created_at') {
            $query->orderBy('created_at', $order)->orderBy('id', $order);
        } elseif ($sortColumn === 'updated_at') {
            $query->orderByRaw("COALESCE(updated_at, created_at) {$order}")->orderBy('id', $order);
        } elseif (in_array($sortColumn, ['title', 'keyword', 'category', 'created_by', 'updated_by', 'start_time'])) {
            $query->orderBy($sortColumn, $order)->orderBy('id', $order);
        } else {
            $query->orderBy('created_at', 'desc')->orderBy('id', 'desc');
        }

        $news = $query->paginate($request->per_page ?? 20);

        if ($request->has('only_first_image')) {
            $news->getCollection()->transform(function ($item) {
                $img = null;
                if (!empty($item->content)) {
                    if (preg_match('/<img[^>]+src=[\'"]([^\'"]+)[\'"]/i', $item->content, $match)) {
                        $img = $match[1];
                    }
                }
                $item->first_content_image = $img;
                unset($item->content); // Drop the heavy content so response payload is tiny
                return $item;
            });
        }

        return response()->json($news);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'content' => 'required',
        ]);

        $data = $request->except(['id', '_method']);

        // Handle attachments JSON decode if sent as string from FormData
        if ($request->has('attachments')) {
            if (is_string($request->attachments)) {
                $decoded = json_decode($request->attachments, true);
                $data['attachments'] = is_array($decoded) ? $decoded : [];
            } else {
                $data['attachments'] = $request->attachments;
            }
        }

        // Handle file upload if present
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            $sanitizedName = preg_replace('/[^a-zA-Z0-9.\-_]/', '_', $originalName);
            $filename = time() . '_' . $sanitizedName . '.' . $extension;
            $path = $file->storeAs('news_attachments', $filename, 'public');
            $data['attachment'] = $path;
        } elseif ($request->filled('attachment_path')) {
            $data['attachment'] = $request->attachment_path;
        }

        try {
            $news = \App\Models\News::create($data);
            \App\Services\ActivityLogger::log('CREATE', 'NEWS', "Membuat artikel berita baru: '{$news->title}'", ['id' => $news->id, 'category' => $news->category]);
            return response()->json($news, 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error creating news: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Gagal menyimpan berita: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $news = \App\Models\News::find($id);
        if (!$news)
            return response()->json(['message' => 'Not found'], 404);
        return response()->json($news);
    }

    public function update(Request $request, $id)
    {
        $news = \App\Models\News::find($id);
        if (!$news)
            return response()->json(['message' => 'Not found'], 404);

        $request->validate([
            'title' => 'required',
        ]);

        $data = $request->except(['id', '_method']);

        // Handle attachments JSON decode if sent as string from FormData
        if ($request->has('attachments')) {
            if (is_string($request->attachments)) {
                $decoded = json_decode($request->attachments, true);
                $data['attachments'] = is_array($decoded) ? $decoded : [];
            } else {
                $data['attachments'] = $request->attachments;
            }
        }

        if ($request->hasFile('attachment')) {
            // Delete old file if exists
            if ($news->attachment && \Illuminate\Support\Facades\Storage::disk('public')->exists($news->attachment)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($news->attachment);
            }
            $file = $request->file('attachment');
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            $sanitizedName = preg_replace('/[^a-zA-Z0-9.\-_]/', '_', $originalName);
            $filename = time() . '_' . $sanitizedName . '.' . $extension;
            $path = $file->storeAs('news_attachments', $filename, 'public');
            $data['attachment'] = $path;
        } elseif ($request->has('attachment_path')) {
            // If manual path provided (e.g. from pre-upload), use it. Or null if empty
            $data['attachment'] = $request->attachment_path;
        }

        try {
            $news->update($data);
            \App\Services\ActivityLogger::log('UPDATE', 'NEWS', "Memperbarui artikel berita: '{$news->title}'", ['id' => $news->id, 'category' => $news->category]);
            return response()->json($news);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error updating news: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Gagal memperbarui berita: ' . $e->getMessage()], 500);
        }
    }
    
    public function destroy($id)
    {
        $news = \App\Models\News::find($id);
        if ($news) {
            $title = $news->title;
            $this->deleteAttachments($news);
            $news->delete();
            \App\Services\ActivityLogger::log('DELETE', 'NEWS', "Menghapus artikel berita: '{$title}'", ['id' => $id]);
        }
        return response()->json(['message' => 'Deleted']);
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $request->ids;
        if (is_array($ids)) {
            $newsItems = \App\Models\News::whereIn('id', $ids)->get();
            $titles = $newsItems->pluck('title')->implode(', ');
            foreach ($newsItems as $news) {
                $this->deleteAttachments($news);
                $news->delete();
            }
            \App\Services\ActivityLogger::log('DELETE', 'NEWS', "Menghapus secara massal artikel berita: {$titles}", ['ids' => $ids]);
            return response()->json(['message' => 'News deleted successfully']);
        }
        return response()->json(['message' => 'Invalid data'], 400);
    }

    private function deleteAttachments($news)
    {
        $pathsToDelete = [];
        if ($news->attachment) {
            $pathsToDelete[] = $news->attachment;
        }

        $attachmentsArray = [];
        if (is_array($news->attachments)) {
            $attachmentsArray = $news->attachments;
        } elseif (is_string($news->attachments)) {
            $decoded = json_decode($news->attachments, true);
            if (is_array($decoded)) {
                $attachmentsArray = $decoded;
            }
        }

        foreach ($attachmentsArray as $att) {
            if (is_array($att) && isset($att['path'])) {
                $pathsToDelete[] = $att['path'];
            } elseif (is_string($att)) {
                $pathsToDelete[] = $att;
            }
        }

        foreach ($pathsToDelete as $path) {
            // Clean path: remove leading '/storage/' or 'storage/'
            $cleanPath = preg_replace('/^\/?storage\//', '', $path);
            
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($cleanPath)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($cleanPath);
            }
            
            $filename = basename($cleanPath);
            if ($filename) {
                // Try to delete from root news_attachments just in case
                $rootPath = 'news_attachments/' . $filename;
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($rootPath)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($rootPath);
                }

                $directories = \Illuminate\Support\Facades\Storage::disk('public')->directories('news_attachments');
                foreach ($directories as $dir) {
                    $dupPath = $dir . '/' . $filename;
                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($dupPath)) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($dupPath);
                    }
                }
            }
        }
    }

    public function uploadAttachment(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // Max 10MB
        ]);

        if ($request->hasFile('file')) {
            try {
                $file = $request->file('file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $sanitizedName = preg_replace('/[^a-zA-Z0-9.\-_]/', '_', $originalName);
                $filename = time() . '_' . $sanitizedName . '.' . $extension;
                
                $unitsJson = $request->input('units');
                $units = $unitsJson ? json_decode($unitsJson, true) : ['general'];
                if (!is_array($units)) $units = [$units];

                // 1. Save to first unit as primary
                $primaryUnit = $units[0] ?? 'general';
                $unitFolder = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $primaryUnit));
                if (empty($unitFolder)) $unitFolder = 'general';
                
                $path = $file->storeAs('news_attachments/' . $unitFolder, $filename, 'public');
                
                // 2. Duplicate to other units for physical organization
                foreach ($units as $index => $unit) {
                    if ($index === 0) continue; // Skip primary already saved
                    $otherFolder = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $unit));
                    if (empty($otherFolder) || $otherFolder === $unitFolder) continue;
                    
                    \Illuminate\Support\Facades\Storage::disk('public')->copy($path, 'news_attachments/' . $otherFolder . '/' . $filename);
                }

                $url = '/storage/' . $path;
                
                \App\Services\ActivityLogger::log('UPLOAD', 'NEWS', "Mengunggah lampiran berita '{$file->getClientOriginalName()}' ke unit: " . implode(', ', $units), ['path' => $path]);

                return response()->json([
                    'path' => $path,
                    'url' => $url,
                    'name' => $file->getClientOriginalName(),
                    'type' => $file->getClientMimeType()
                ]);
            } catch (\Exception $e) {
                return response()->json(['message' => 'Storage error: ' . $e->getMessage()], 500);
            }
        }
        return response()->json(['message' => 'No file uploaded'], 400);
    }
    public function deleteSingleAttachment(Request $request)
    {
        $path = $request->input('path');
        $url = $request->input('url');

        if (!$path && $url) {
            // Extract path from URL (e.g. /storage/news_attachments/...)
            $path = preg_replace('/^\/?storage\//', '', $url);
        }

        if ($path) {
            $cleanPath = preg_replace('/^\/?storage\//', '', $path);
            
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($cleanPath)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($cleanPath);
            }
            
            $filename = basename($cleanPath);
            if ($filename) {
                $rootPath = 'news_attachments/' . $filename;
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($rootPath)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($rootPath);
                }

                $directories = \Illuminate\Support\Facades\Storage::disk('public')->directories('news_attachments');
                foreach ($directories as $dir) {
                    $dupPath = $dir . '/' . $filename;
                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($dupPath)) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($dupPath);
                    }
                }
            }
            
            \App\Services\ActivityLogger::log('DELETE', 'NEWS', "Menghapus lampiran: " . $filename, ['path' => $cleanPath]);
            return response()->json(['message' => 'Attachment deleted']);
        }
        
        return response()->json(['message' => 'No path provided'], 400);
    }

    public function downloadAttachment(Request $request)
    {
        $path = $request->query('path');
        if (!$path) {
            return response()->json(['error' => 'Path is required'], 400);
        }

        // Clean path to remove /storage/ prefix if present
        if (str_starts_with($path, '/storage/')) {
            $path = substr($path, 9);
        }

        $fullPath = storage_path('app/public/' . $path);
        
        if (!file_exists($fullPath)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $fileName = basename($fullPath);
        
        // Format the filename to DDMMYYYY-HHMMSS-[Name]
        if (preg_match('/^(\d{10})_(.+)$/', $fileName, $matches)) {
            $timestamp = (int)$matches[1];
            $originalPart = $matches[2];
            $formattedDate = date('dmY-His', $timestamp);
            $newFileName = $formattedDate . '-' . $originalPart;
        } else {
            $newFileName = date('dmY-His') . '-' . $fileName;
        }

        return response()->download($fullPath, $newFileName);
    }
}
