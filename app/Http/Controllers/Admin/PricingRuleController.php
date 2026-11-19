<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Format;
use App\Models\PricingHoliday;
use App\Models\PricingRule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PricingRuleController extends Controller
{
    public function index()
    {
        $rules = PricingRule::with('format')
            ->orderByDesc('priority')
            ->orderBy('name')
            ->paginate(15);

        $formats = Format::orderBy('name')->get();

        $holidays = PricingHoliday::orderBy('holiday_date')->get();

        return view('admin.pricing.index', compact(
            'rules',
            'formats',
            'holidays'
        ));
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

    public function storeHoliday(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'holiday_date' => [
                'required',
                'date',
                'unique:pricing_holidays,holiday_date',
            ],
        ], [
            'holiday_date.unique' => 'Ngày này đã được thêm.',
        ]);

        PricingHoliday::create($data);

        return back()->with('success', 'Đã thêm ngày lễ.');
    }

    public function destroyHoliday(PricingHoliday $pricingHoliday)
    {
        $pricingHoliday->delete();

        return back()->with('success', 'Đã xóa ngày lễ.');
    }

    private function validateRule(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'seat_type' => ['nullable', 'in:standard,vip,couple'],
            'format_id' => ['nullable', 'integer', 'exists:formats,id'],
            'day_type' => ['required', 'in:any,weekday,weekend,holiday'],
            'time_start' => [
                'nullable',
                'date_format:H:i',
                'required_with:time_end',
            ],
            'time_end' => [
                'nullable',
                'date_format:H:i',
                'required_with:time_start',
            ],
            'amount_type' => ['required', 'in:surcharge,fixed'],
            'amount' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'priority' => ['required', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}