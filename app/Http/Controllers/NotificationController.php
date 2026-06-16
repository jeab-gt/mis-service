<?php

namespace App\Http\Controllers;

use App\Models\MisNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = MisNotification::where('user_id', auth()->id())
            ->latest()
            ->paginate(20);
        return view('notifications.index', compact('notifications'));
    }

    public function recent()
    {
        $locale = app()->getLocale();
        $notifications = MisNotification::where('user_id', auth()->id())
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn($n) => [
                'id'         => $n->id,
                'type'       => $n->type,
                'title'      => $locale === 'th' ? $n->title_th : $n->title_en,
                'body'       => $locale === 'th' ? $n->body_th  : $n->body_en,
                'read_at'    => $n->read_at?->toISOString(),
                'created_at' => $n->created_at->diffForHumans(),
                'payload'    => $n->payload ?? [],
            ]);

        return response()->json([
            'notifications' => $notifications,
            'unreadCount'   => MisNotification::where('user_id', auth()->id())->whereNull('read_at')->count(),
        ]);
    }

    public function markRead(MisNotification $notification, Request $request)
    {
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }
        $notification->markAsRead();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true]);
        }
        return back()->with('success', 'อ่านแล้ว');
    }

    public function markAllRead(Request $request)
    {
        MisNotification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true]);
        }
        return back()->with('success', 'อ่านทั้งหมดแล้ว');
    }
}
