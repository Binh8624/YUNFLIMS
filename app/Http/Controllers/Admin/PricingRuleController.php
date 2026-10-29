<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Format;
use App\Models\PricingRule;
use Illuminate\Http\Request;

class PricingRuleController extends Controller
{
    public function index()
    {
        $rules = PricingRule::with('format')->orderByDesc('priority')->orderBy('name')->paginate(15);
        $formats = Format::orderBy('name')->get();

        return view('admin.pricing.index', compact('rules', 'formats'));
    }

    public function store(Request $request)
    {
        PricingRule::create($this->validateRule($request));

        return back()->with('success', 'Đã tạo quy tắc giá.');
    }

    public function update(Request $request, PricingRule $pricingRule)
    {
        $pricingRule->update($this->validateRule($request));

        return back()->with('success', 'Đã cập nhật quy tắc giá.');
    }

    public function destroy(PricingRule $pricingRule)
    {
        $pricingRule->delete();

        return back()->with('success', 'Đã xóa quy tắc giá.');
    }

    private function validateRule(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'seat_type' => ['nullable', 'in:standard,vip,couple'],
            'format_id' => ['nullable', 'integer', 'exists:formats,id'],
            'day_type' => ['required', 'in:any,weekday,weekend'],
            'time_start' => ['nullable', 'date_format:H:i', 'required_with:time_end'],
            'time_end' => ['nullable', 'date_format:H:i', 'required_with:time_start', 'after:time_start'],
            'amount_type' => ['required', 'in:surcharge,fixed'],
            'amount' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'priority' => ['required', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['required', 'boolean'],
        ]);

        if (($data['time_start'] ?? null) && ! ($data['time_end'] ?? null)) {
            $request->validate(['time_end' => ['required', 'date_format:H:i']]);
        }

        return $data;
    }
}
