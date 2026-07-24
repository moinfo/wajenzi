<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces that the acting user holds the role required by the approval step
 * they are trying to action.
 *
 * The RingleSoft package's Approvable::approve()/reject()/return()/discard()
 * only check that the document is submitted and a next step exists — they never
 * verify the actor's role, and the controller even accepts a request-supplied
 * user_id. That let an Accountant approve a step reserved for the Managing
 * Director. This middleware closes that hole at the route boundary using the
 * package's own next-step definition.
 *
 * Registered via config('process_approval.approval_controller_middlewares').
 */
class EnsureApprovalStepRole
{
    /** Approval actions that act on the current step and therefore require its role. */
    private const GATED_ACTIONS = ['approve', 'reject', 'return', 'discard'];

    public function handle(Request $request, Closure $next): Response
    {
        $action = $request->route()
            ? \Illuminate\Support\Str::afterLast($request->route()->getName() ?? '', '.')
            : null;

        if (!in_array($action, self::GATED_ACTIONS, true)) {
            return $next($request);
        }

        $modelClass = $request->input('model_name');
        $id = $request->route('id');

        if ($modelClass && class_exists($modelClass) && $id) {
            $model = $modelClass::find($id);

            if ($model && method_exists($model, 'nextApprovalStep')) {
                $nextStep = $model->nextApprovalStep();

                // Only enforce when there is a pending step with a defined role.
                // If there is no next step, let the package handle it (it throws
                // its own "no further steps" error) so we don't change that path.
                if ($nextStep && $nextStep->role && !Auth::user()?->hasRole($nextStep->role)) {
                    $message = 'You do not have the required role (' . $nextStep->role->name
                        . ') to ' . $action . ' this approval step.';

                    if ($request->wantsJson()) {
                        abort(403, $message);
                    }

                    return back()->with('error', $message);
                }
            }
        }

        return $next($request);
    }
}
