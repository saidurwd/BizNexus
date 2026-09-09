<?php

namespace Modules\Core\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Auth::user()->notifications();

        if ($request->filled('type')) {
            $query->where('type', 'like', '%' . $request->get('type') . '%');
        }

        if ($request->filled('read')) {
            if ($request->get('read') === 'unread') {
                $query->whereNull('read_at');
            } elseif ($request->get('read') === 'read') {
                $query->whereNotNull('read_at');
            }
        }

        $notifications = $query->orderByDesc('created_at')->paginate(25);

        $types = DatabaseNotification::select('type')->distinct()->orderBy('type')->pluck('type');

        return view('core.notifications.index', compact('notifications', 'types'));
    }

    public function show(string $id)
    {
        $notification = Auth::user()->notifications()->where('id', $id)->firstOrFail();

        if (!$notification->read_at) {
            $notification->markAsRead();
        }

        return view('core.notifications.show', compact('notification'));
    }

    public function markAsRead(Request $request, string $id)
    {
        $notification = Auth::user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }

    public function destroy(string $id)
    {
        $notification = Auth::user()->notifications()->where('id', $id)->firstOrFail();
        $notification->delete();

        return back()->with('success', 'Notification deleted.');
    }

    public function destroyAll()
    {
        Auth::user()->notifications()->delete();

        return back()->with('success', 'All notifications deleted.');
    }
}
