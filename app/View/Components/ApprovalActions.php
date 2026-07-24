<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use RingleSoft\LaravelProcessApproval\View\Components\ApprovalActions as BaseApprovalActions;

/**
 * Overrides the ringlesoft approval-actions component so the bootstrap variant
 * renders a tracked Blade view (resources/views/components/ringlesoft/approval-actions-bs.blade.php)
 * instead of the hardcoded vendor file. The tracked view additionally displays each
 * approver's comment as visible text. Registered in App\Providers\AppServiceProvider.
 */
class ApprovalActions extends BaseApprovalActions
{
    public function render(): Closure|Htmlable|View|string
    {
        if (!count($this->modelApprovalSteps)) {
            return "";
        }

        if (Str::of(config('process_approval.css_library'))->startsWith('bootstrap')) {
            $versionNumber = Str::of(config('process_approval.css_library'))->after('bootstrap')->toString();
            $bsVersionAttribute = ($versionNumber === '3') ? '' : 'bs-';

            return view('components.ringlesoft.approval-actions-bs', compact('bsVersionAttribute'));
        }

        // Tailwind / plain variants fall back to the package implementation.
        return parent::render();
    }
}
