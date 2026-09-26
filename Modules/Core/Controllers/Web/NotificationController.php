<?php

namespace Modules\Core\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Auth::user()->notifications();

        if ($request->filled('type')) {
            $query->where('type', 'like', '%'.$request->get('type').'%');
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

    /**
     * Unread count and latest notifications for the navbar bell (AdminLTE navbar-notification contract).
     */
    public function dropdown(): JsonResponse
    {
        $user = Auth::user();
        $unread = $user->unreadNotifications()->count();

        return response()->json([
            'label' => $unread,
            'label_color' => 'danger',
            'icon_color' => $unread > 0 ? 'warning' : null,
            'dropdown' => view('core.notifications._dropdown', [
                'unread' => $unread,
                'notifications' => $user->notifications()->latest()->limit(6)->get(),
            ])->render(),
        ]);
    }

    /**
     * Mark the notification read and go to what it is about.
     */
    public function open(string $id): RedirectResponse
    {
        $notification = Auth::user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();
        $url = $notification->data['url'] ?? null;

        return is_string($url) && str_starts_with($url, url('/'))
            ? redirect()->to($url)
            : redirect()->route('core.notifications.show', $notification->id);
    }

    public function show(string $id)
    {
        $notification = Auth::user()->notifications()->where('id', $id)->firstOrFail();

        if (! $notification->read_at) {
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
