<?php

namespace App\Http\Controllers;

use App\Services\MembershipStatusService;

class MemberPortalController extends Controller
{
    public function membership(MembershipStatusService $service)
    {
        $member = auth()->user()->member;
        $subscriptions = $member->subscriptions()->with(['package', 'payment', 'trainer.user', 'trainerSessions'])->latest('end_date')->get();
        $currentSubscription = $member->subscriptions()
            ->with(['package', 'payment', 'trainer.user', 'trainerSessions'])
            ->current()
            ->latest('end_date')
            ->first();

        return view('membership.index', [
            'member' => $member,
            'subscriptions' => $subscriptions,
            'currentSubscription' => $currentSubscription,
            'status' => $service->resolve($currentSubscription),
        ]);
    }

    public function payments()
    {
        return view('payments.index', ['payments' => auth()->user()->member->payments()->with('subscription.package')->latest('created_at')->paginate(15)]);
    }
}
