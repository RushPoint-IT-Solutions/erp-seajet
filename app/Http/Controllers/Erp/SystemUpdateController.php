<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Services\Audit\ActivityLogger;
use App\Services\System\SystemUpdateService;
use Illuminate\Http\Request;

class SystemUpdateController extends Controller
{
    public function index(SystemUpdateService $updater)
    {
        return view('erp.system-update.index', ['update' => $updater->status()]);
    }

    public function status(SystemUpdateService $updater)
    {
        return response()->json($updater->status());
    }

    public function run(Request $request, SystemUpdateService $updater)
    {
        $data = $request->validate(['confirmation' => 'required|in:UPDATE']);
        try {
            $state = $updater->queue(auth()->id(), auth()->user()->company_id ?? null);
        } catch (\Throwable $e) {
            return back()->withErrors(['update' => $e->getMessage()]);
        }

        app(ActivityLogger::class)->log('system_update_queued', 'system_update', null, [], [
            'before_version' => $state['before_version'] ?? null,
            'status' => $state['status'] ?? 'queued',
        ], auth()->user()->company_id ?? null);

        return redirect()->route('erp.system-update.index')->with('status', 'System update queued. This page will show its progress.');
    }
}
