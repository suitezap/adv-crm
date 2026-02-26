@php
    use SuiteZap\LawFirm\SaaS\Services\MotherShipService;
    use Carbon\Carbon;

    try {
        $__sub = MotherShipService::getCurrentSubscription();
        if ($__sub) {
            $__expiresAt = $__sub->expires_at ? Carbon::parse($__sub->expires_at) : null;
            $__daysLeft = $__expiresAt ? (int) now()->diffInDays($__expiresAt, false) : null;
            $__isExpired = $__sub->status === 'inactive' || ($__expiresAt && now()->greaterThanOrEqualTo($__expiresAt));
            $__isWarning = !$__isExpired && $__daysLeft !== null && $__daysLeft <= 7;
        } else {
            $__isExpired = false;
            $__isWarning = false;
            $__daysLeft = null;
        }
    } catch (\Throwable $e) {
        $__isExpired = false;
        $__isWarning = false;
        $__daysLeft = null;
    }
@endphp

@if($__isExpired)
    <div
        style="background:#fee2e2;border-bottom:1px solid #fca5a5;color:#b91c1c;padding:5px 16px;font-size:0.78rem;font-weight:600;display:flex;align-items:center;gap:8px;z-index:10002;position:sticky;top:0;">
        <span style="font-size:1rem;">⛔</span>
        Assinatura Inativa — Acesso restrito.
        <a href="{{ route('admin.lawfirm.saas.index') }}"
            style="margin-left:6px;text-decoration:underline;font-weight:700;">Ver detalhes</a>
    </div>
@elseif($__isWarning)
    <div
        style="background:#fffbc8;border-bottom:1px solid #fde047;color:#a16207;padding:5px 16px;font-size:0.78rem;font-weight:600;display:flex;align-items:center;gap:8px;z-index:10002;position:sticky;top:0;">
        <span style="font-size:1rem;">⚠️</span>
        Assinatura vence em {{ $__daysLeft }} dia{{ $__daysLeft != 1 ? 's' : '' }}.
        <a href="{{ route('admin.lawfirm.saas.index') }}"
            style="margin-left:6px;text-decoration:underline;font-weight:700;">Ver assinatura</a>
    </div>
@endif