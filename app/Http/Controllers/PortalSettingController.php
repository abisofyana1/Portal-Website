<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PortalSetting;

class PortalSettingController extends Controller
{
    public function index()
    {
        return response()->json(PortalSetting::orderBy('order_no')->get());
    }

    public function updateAll(Request $request)
    {
        $settings = $request->input('settings');

        foreach ($settings as $settingData) {
            PortalSetting::updateOrCreate(
                ['id' => $settingData['id'] ?? null],
                [
                    'section' => $settingData['section'],
                    'category' => $settingData['category'] ?? null,
                    'title' => $settingData['title'] ?? null,
                    'url' => $settingData['url'] ?? null,
                    'image_name' => $settingData['image_name'] ?? null,
                    'order_no' => $settingData['order_no'] ?? 1,
                    'is_active' => $settingData['is_active'] ?? true,
                    'target_unit' => $settingData['target_unit'] ?? null,
                    'parent_id' => $settingData['parent_id'] ?? null,
                ]
            );
        }

        \App\Services\ActivityLogger::log('UPDATE', 'PORTAL_SETTING', "Memperbarui konfigurasi menu dan tata letak portal secara massal", ['count' => count($settings)]);

        return response()->json(['message' => 'Settings updated successfully']);
    }

    public function store(Request $request)
    {
        $data = $request->only(['section', 'category', 'title', 'url', 'order_no', 'is_active', 'parent_id']);

        if ($request->filled('target_unit')) {
            $data['target_unit'] = $request->target_unit; // Should be json encoded string or array depending on axios
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            // Move file to public/images directory
            $file->move(public_path('images'), $filename);
            $data['image_name'] = $filename;
        }

        $setting = PortalSetting::create($data);

        \App\Services\ActivityLogger::log('CREATE', 'PORTAL_SETTING', "Menambahkan item menu/konfigurasi portal baru: '{$setting->title}' di bagian '{$setting->section}'", ['id' => $setting->id]);

        return response()->json([
            'message' => 'Setting created successfully',
            'data' => $setting
        ]);
    }

    public function updateSingle(Request $request, $id)
    {
        $setting = PortalSetting::findOrFail($id);

        $data = $request->only(['section', 'title', 'category', 'url', 'is_active', 'order_no', 'parent_id']);

        if ($request->filled('target_unit')) {
            $data['target_unit'] = $request->target_unit;
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            // Move file to public/images directory
            $file->move(public_path('images'), $filename);
            $data['image_name'] = $filename;
        }

        $setting->update($data);

        \App\Services\ActivityLogger::log('UPDATE', 'PORTAL_SETTING', "Memperbarui item konfigurasi portal '{$setting->title}' di bagian '{$setting->section}'", ['id' => $setting->id]);

        return response()->json([
            'message' => 'Setting updated successfully',
            'data' => $setting
        ]);
    }

    public function destroy($id)
    {
        $setting = PortalSetting::findOrFail($id);
        $title = $setting->title;
        $section = $setting->section;
        $setting->delete();

        \App\Services\ActivityLogger::log('DELETE', 'PORTAL_SETTING', "Menghapus item konfigurasi portal '{$title}' dari bagian '{$section}'", ['id' => $id]);

        return response()->json(['message' => 'Setting deleted successfully']);
    }
}
