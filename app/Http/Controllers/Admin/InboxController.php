<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InboxController extends Controller
{
    /**
     * Full inbox page — paginated list of all notifications for this admin.
     */
    public function index(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        $items = AdminNotification::forAdmin($admin)
            ->when($request->status === 'unread', fn ($q) => $q->whereNull('read_at'))
            ->when($request->status === 'read', fn ($q) => $q->whereNotNull('read_at'))
            ->when($request->event, fn ($q, $e) => $q->where('event_key', $e))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $unreadCount = AdminNotification::forAdmin($admin)->unread()->count();

        return view('admin.inbox.index', compact('items', 'unreadCount'));
    }

    /**
     * Open a notification: mark it read, then redirect to its deep link
     * (or back to the inbox if the event has no link).
     */
    public function open(AdminNotification $adminNotification)
    {
        $admin = Auth::guard('admin')->user();
        abort_unless($adminNotification->admin_id === $admin->id, 403);

        $adminNotification->markAsRead();

        if ($adminNotification->link) {
            return redirect()->to($adminNotification->link);
        }

        return redirect()->route('admin.inbox.index');
    }

    /**
     * Mark every unread notification as read for the logged-in admin.
     */
    public function markAllRead(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        AdminNotification::forAdmin($admin)
            ->unread()
            ->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * AJAX poll endpoint — returns the live unread count. Used by the bell
     * icon to refresh without a full page load.
     */
    public function unreadCount()
    {
        $admin = Auth::guard('admin')->user();

        return response()->json([
            'unread' => AdminNotification::forAdmin($admin)->unread()->count(),
        ]);
    }
}
