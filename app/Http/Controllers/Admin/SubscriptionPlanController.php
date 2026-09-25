<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SavePlanRequest;
use App\Models\Section;
use App\Models\SubscriptionPlan;
use App\Support\ImageUploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/* DDE-Mart Admin — subscription plans (original controller). */
class SubscriptionPlanController extends Controller
{
    public function index(): View
    {
        $plans = SubscriptionPlan::orderBy('price')->paginate(15);

        return view('admin.finance.plans.index', compact('plans'));
    }

    public function create(): View
    {
        return view('admin.finance.plans.form', $this->formData(new SubscriptionPlan()));
    }

    public function store(SavePlanRequest $request): RedirectResponse
    {
        $plan = SubscriptionPlan::create($this->payload($request));

        return redirect()->route('admin.plans.index')->with('success', "Plan '{$plan->name}' created.");
    }

    public function edit(SubscriptionPlan $plan): View
    {
        return view('admin.finance.plans.form', $this->formData($plan));
    }

    public function update(SavePlanRequest $request, SubscriptionPlan $plan): RedirectResponse
    {
        $plan->update($this->payload($request, $plan));

        return redirect()->route('admin.finance.plans.index')->with('success', "Plan '{$plan->name}' updated.");
    }

    public function destroy(SubscriptionPlan $plan): RedirectResponse
    {
        ImageUploads::delete($plan->image_path);
        $plan->delete();

        return redirect()->route('admin.plans.index')->with('success', "Plan '{$plan->name}' deleted.");
    }

    protected function formData(SubscriptionPlan $plan): array
    {
        return [
            'plan' => $plan,
            'sections' => Section::orderBy('name')->get(),
            'method' => $plan->exists ? 'PUT' : 'POST',
            'action' => $plan->exists
                ? route('admin.plans.update', $plan)
                : route('admin.plans.store'),
        ];
    }

    protected function payload(SavePlanRequest $request, ?SubscriptionPlan $plan = null): array
    {
        $data = $request->safe()->except(['image', 'remove_image']);
        $data['is_commission_plan'] = $request->boolean('is_commission_plan');
        $data['is_active'] = $request->boolean('is_active');
        // -1 in the form means unlimited → store null.
        foreach (['item_limit', 'order_limit'] as $limit) {
            if (array_key_exists($limit, $data) && (int) $data[$limit] < 0) {
                $data[$limit] = null;
            }
        }

        if ($request->boolean('remove_image')) {
            ImageUploads::delete($plan?->image_path);
            $data['image_path'] = null;
        } else {
            $data['image_path'] = ImageUploads::replace($request->file('image'), $plan?->image_path, 'plans');
        }

        return $data;
    }
}
