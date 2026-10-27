<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Store;
use App\Services\PayrollService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function __construct(private PayrollService $payrolls)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('hr.view');
        $company = Workspace::company();
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $base = Payroll::query()
            ->forCompany($company->id)
            ->where('year', $year)
            ->where('month', $month)
            ->search($request->string('q')->toString())
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->integer('employee_id')))
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->integer('store_id')));

        $payrolls = (clone $base)->with(['employee', 'store'])->orderByDesc('id')->paginate(20)->withQueryString();

        $stats = [
            'count' => (clone $base)->count(),
            'gross' => (float) (clone $base)->sum('gross'),
            'net' => (float) (clone $base)->sum('net'),
            'paid' => (float) (clone $base)->sum('paid_amount'),
            'pending' => (clone $base)->where('status', 'pending')->count(),
        ];

        return view('hr.payroll.index', [
            'payrolls' => $payrolls,
            'stats' => $stats,
            'year' => $year,
            'month' => $month,
            'statuses' => Payroll::STATUSES,
            'employees' => Employee::query()->forCompany($company->id)->orderBy('last_name')->get(),
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'filters' => $request->only(['q', 'status', 'employee_id', 'store_id', 'year', 'month']),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('hr.create');
        $company = Workspace::company();
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);
        $preview = null;

        if ($request->filled('employee_id')) {
            $employee = Employee::query()->forCompany($company->id)->findOrFail($request->integer('employee_id'));
            $preview = $this->payrolls->previewCalculate($employee, $year, $month, $request->only([
                'prime', 'bonus', 'advance', 'retention', 'pay_type', 'base_rate',
            ]));
        }

        return view('hr.payroll.create', [
            'year' => $year,
            'month' => $month,
            'preview' => $preview,
            'payTypes' => Payroll::PAY_TYPES,
            'employees' => Employee::query()->forCompany($company->id)->where('status', 'active')->orderBy('last_name')->get(),
            'selectedEmployeeId' => $request->input('employee_id'),
            'overrides' => $request->only(['prime', 'bonus', 'advance', 'retention', 'pay_type', 'base_rate', 'notes']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('hr.create');
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'pay_type' => ['nullable', 'in:hourly,daily,weekly,monthly'],
            'base_rate' => ['nullable', 'numeric', 'min:0'],
            'prime' => ['nullable', 'numeric', 'min:0'],
            'bonus' => ['nullable', 'numeric', 'min:0'],
            'advance' => ['nullable', 'numeric', 'min:0'],
            'retention' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $employee = Employee::query()
            ->forCompany(Workspace::company()->id)
            ->findOrFail($data['employee_id']);

        $payroll = $this->payrolls->calculateAndSave($employee, (int) $data['year'], (int) $data['month'], $data);

        return redirect()->route('hr.payroll.show', $payroll)->with('success', 'Bulletin de paie calculé.');
    }

    public function show(Payroll $payroll): View
    {
        $this->authorize('hr.view');
        $this->ensureCompany($payroll);
        $payroll->load(['employee.store', 'store', 'creator']);

        return view('hr.payroll.show', [
            'payroll' => $payroll,
            'paymentMethods' => Payroll::PAYMENT_METHODS,
        ]);
    }

    public function bulkCalculate(Request $request): RedirectResponse
    {
        $this->authorize('hr.create');
        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
        ]);

        $saved = $this->payrolls->bulkCalculate(
            Workspace::company()->id,
            (int) $data['year'],
            (int) $data['month'],
            $data['employee_ids'] ?? null
        );

        return redirect()
            ->route('hr.payroll.index', ['year' => $data['year'], 'month' => $data['month']])
            ->with('success', $saved->count().' bulletin(s) calculé(s).');
    }

    public function markPaid(Request $request, Payroll $payroll): RedirectResponse
    {
        $this->authorize('hr.update');
        $this->ensureCompany($payroll);
        $data = $request->validate([
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'in:cash,transfer,check'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->payrolls->markPaid($payroll, $data);

        return back()->with('success', 'Paiement enregistré.');
    }

    public function stats(Request $request): View
    {
        $this->authorize('hr.view');
        $company = Workspace::company();
        $year = (int) $request->input('year', now()->year);

        $byMonth = collect(range(1, 12))->map(function (int $m) use ($company, $year) {
            $q = Payroll::query()->forCompany($company->id)->where('year', $year)->where('month', $m);

            return [
                'month' => $m,
                'label' => sprintf('%02d', $m),
                'net' => (float) (clone $q)->sum('net'),
                'gross' => (float) (clone $q)->sum('gross'),
                'count' => (clone $q)->count(),
            ];
        });

        $top = Payroll::query()
            ->forCompany($company->id)
            ->where('year', $year)
            ->with('employee')
            ->get()
            ->groupBy('employee_id')
            ->map(fn ($g) => [
                'name' => $g->first()->employee?->full_name ?? '—',
                'net' => (float) $g->sum('net'),
            ])
            ->sortByDesc('net')
            ->take(8)
            ->values();

        return view('hr.payroll.stats', compact('byMonth', 'top', 'year'));
    }

    protected function ensureCompany(Payroll $payroll): void
    {
        if ($payroll->company_id !== Workspace::company()?->id) {
            abort(404);
        }
    }
}
