<?php

namespace App\Http\Controllers;

use App\Services\MembershipStatusService;

class MemberPortalController extends Controller
{
    public function membership(MembershipStatusService $service)
    {
        $member = auth()->user()->member;
        $subscriptions = $member->subscriptions()->with(['package', 'payment'])->latest('end_date')->get();

        return view('membership.index', ['member' => $member, 'subscriptions' => $subscriptions, 'status' => $service->resolve($subscriptions->first())]);
    }

    public function payments()
    {
        return view('payments.index', ['payments' => auth()->user()->member->payments()->with('subscription.package')->latest('created_at')->paginate(15)]);
    }

    public function programs()
    {
        return view('workout-programs.member-index', ['assignments' => auth()->user()->member->programs()->with(['program.exercises.exercise', 'trainer.user'])->latest('assigned_date')->get()]);
    }
}
