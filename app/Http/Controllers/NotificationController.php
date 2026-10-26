<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(15);

        return view('notifications.index', compact('notifications'));
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $ownedNotification = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        $ownedNotification->markAsRead();

        return redirect()->route('notifications.index')
            ->with('success', 'Notifikasi ditandai sudah dibaca.');
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update([
            'read_at' => now(),
        ]);

        return redirect()->route('notifications.index')
            ->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
