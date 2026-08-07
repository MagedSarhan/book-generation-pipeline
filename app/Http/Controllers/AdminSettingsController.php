<?php

namespace App\Http\Controllers;

use App\Models\ApplicationSetting;
use Illuminate\Http\Request;

class AdminSettingsController extends Controller
{
    public function index()
    {
        $settings = [
            'default_quality' => ApplicationSetting::get('default_quality', 'high'),
            'default_resolution' => ApplicationSetting::get('default_resolution', 'auto'),
            'default_output_format' => ApplicationSetting::get('default_output_format', 'png'),
            'default_batch_size' => ApplicationSetting::get('default_batch_size', 10),
            'max_variants' => ApplicationSetting::get('max_variants', 4),
            'concurrency' => ApplicationSetting::get('concurrency', 3),
        ];

        return view('admin.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'default_quality' => 'required|in:low,medium,high',
            'default_resolution' => 'required|string',
            'default_output_format' => 'required|in:png,jpeg,webp',
            'default_batch_size' => 'required|integer|min:1|max:100',
            'max_variants' => 'required|integer|min:1|max:4',
            'concurrency' => 'required|integer|min:1|max:10',
        ]);

        foreach ($validated as $k => $v) {
            ApplicationSetting::set($k, $v);
        }

        return back()->with('success', 'تم حفظ الإعدادات بنجاح.');
    }
}
