<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }

        if (!$user->is_active) {
            \Illuminate\Support\Facades\Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors(['login_identifier' => 'Tài khoản của bạn đã bị vô hiệu hóa.']);
        }

        // Nếu là nhân viên (quyền = 3), chỉ được truy cập một số route nhất định
        if ($user->permission == 3) {
            $allowedRoutes = [
                'backend.calendar.index',
                'backend.calendar.swap',
                'backend.attendance-requests.index',
                'backend.attendance-requests.create',
                'backend.attendance-requests.store',
                'backend.attendance-requests.edit',
                'backend.attendance-requests.update',
                'backend.attendance-requests.destroy',
                'backend.attendance-requests.check-limit',
                'backend.attendance-requests.bulk-approve',
                'backend.admin.logout',
                'backend.admin.dashboard',
                'backend.my-evaluations.index',
                'backend.my-evaluations.create',
                'backend.my-evaluations.store',
                'backend.my-evaluations.edit',
                'backend.my-evaluations.update',
                'backend.evaluation-approvals.index',
                'backend.evaluation-approvals.edit',
                'backend.evaluation-approvals.update',
            ];

            if (!in_array($request->route()?->getName(), $allowedRoutes)) {
                abort(403, 'Bạn không có quyền truy cập trang này.');
            }
        }

        abort_unless(in_array($user->permission, [1, 2, 3]), 403);

        return $next($request);
    }
}
