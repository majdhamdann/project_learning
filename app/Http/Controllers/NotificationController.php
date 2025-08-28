<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
   public function index()
    {
        $user = Auth::user();
        $notifications = $user->notifications()->paginate(20);

        return response()->json([
            'data' => $notifications,
            'unread_count' => $user->unreadNotifications->count()
        ]);
    }

    // جلب الإشعارات غير المقروءة فقط
    public function unread()
    {
        $user = Auth::user();
        $notifications = $user->unreadNotifications()->paginate(20);

        return response()->json([
            'data' => $notifications,
            'unread_count' => $notifications->count()
        ]);
    }

    // تحديد إشعار كمقروء
    public function markAsRead($id)
    {
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read']);
    }

    // تحديد جميع الإشعارات كمقروءة
    public function markAllAsRead()
    {
        $user = Auth::user();
        $user->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read']);
    }

    // حذف إشعار
    public function destroy($id)
    {
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->delete();

        return response()->json(['message' => 'Notification deleted successfully']);
    }

    // جلب عدد الإشعارات غير المقروءة
    public function unreadCount()
    {
        $user = Auth::user();
        $count = $user->unreadNotifications()->count();

        return response()->json(['unread_count' => $count]);
    }
}
