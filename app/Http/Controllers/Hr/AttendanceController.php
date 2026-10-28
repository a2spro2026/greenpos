<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Store;
use App\Services\AttendanceService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $attendance)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('hr.view');
        $company = Workspace::company();

        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);
        $view = $request->string('view', 'list')->toString();
        if (! in_array($view, ['list', 'calendar', 'summary'], true)) {
            $view = 'list';
        }

        $query = Attendance::query()
            ->forCompany($company->id)
            ->with(['employee', 'store'])
            ->search($request->string('q')->toString())
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->integer('employee_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->integer('store_id')));

        if ($view === 'calendar' || $view === 'summary' || ! $request->filled('from')) {
            $start = sprintf('%04d-%02d-01', $year, $month);
            $end = date('Y-m-t', strtotime($start));
            $query->whereBetween('date', [$start, $end]);
        } else {
            $query->when($request->filled('from'), fn ($q) => $q->whereDate('date', '>=', $request->string('from')))
                ->when($request->filled('to'), fn ($q) => $q->whereDate('date', '<=', $request->string('to')));
        }

        $records = $query->orderByDesc('date')->orderBy('employee_id')->paginate(20)->withQueryString();
        $summary = $this->attendance->monthlySummary(
            $company->id,
            $year,
            $month,
            $request->filled('employee_id') ? $request->integer('employee_id') : null
        );

        return view('hr.attendance.index', [
            'records' => $records,
            'summary' => $summary,
            'view' => $view,
            'year' => $year,
            'month' => $month,
            'statuses' => Attendance::STATUSES,
            'employees' => Employee::query()->forCompany($company->id)->where('status', 'active')->orderBy('last_name')->get(),
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'filters' => $request->only(['q', 'status', 'employee_id', 'store_id', 'from', 'to', 'year', 'month', 'view']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('hr.create');
        $company = Workspace::company();

        return view('hr.attendance.create', [
            'statuses' => Attendance::STATUSES,
            'employees' => Employee::query()->forCompany($company->id)->where('status', 'active')->orderBy('last_name')->get(),
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('hr.create');
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'store_id' => ['nullable', 'exists:stores,id'],
            'date' => ['required', 'date'],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i'],
            'break_minutes' => ['nullable', 'integer', 'min:0'],
            'worked_hours' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:present,absent,leave,late,partial'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->ensureEmployeeCompany((int) $data['employee_id']);
        $this->attendance->upsertDaily($data);

        return redirect()->route('hr.attendance.index')->with('success', 'Présence enregistrée.');
    }

    public function bulkForm(Request $request): View
    {
        $this->authorize('hr.create');
        $company = Workspace::company();
        $date = $request->input('date', now()->toDateString());

        return view('hr.attendance.bulk', [
            'date' => $date,
            'statuses' => Attendance::STATUSES,
            'employees' => Employee::query()->forCompany($company->id)->where('status', 'active')->orderBy('last_name')->get(),
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
        ]);
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $this->authorize('hr.create');
        $request->validate([
            'date' => ['required', 'date'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.employee_id' => ['required', 'exists:employees,id'],
            'rows.*.status' => ['required', 'in:present,absent,leave,late,partial'],
            'rows.*.check_in' => ['nullable', 'date_format:H:i'],
            'rows.*.check_out' => ['nullable', 'date_format:H:i'],
            'rows.*.break_minutes' => ['nullable', 'integer', 'min:0'],
            'rows.*.worked_hours' => ['nullable', 'numeric', 'min:0'],
            'rows.*.notes' => ['nullable', 'string', 'max:500'],
            'store_id' => ['nullable', 'exists:stores,id'],
        ]);

        $date = $request->string('date')->toString();
        $storeId = $request->input('store_id');
        $rows = collect($request->input('rows', []))->map(function (array $row) use ($date, $storeId) {
            return [
                ...$row,
                'date' => $date,
                'store_id' => $storeId,
            ];
        })->all();

        $saved = $this->attendance->bulkEntry($rows);

        return redirect()
            ->route('hr.attendance.index', ['year' => date('Y', strtotime($date)), 'month' => date('n', strtotime($date))])
            ->with('success', $saved->count().' présence(s) enregistrée(s).');
    }

    public function monthlySummary(Request $request): View
    {
        $this->authorize('hr.view');
        $company = Workspace::company();
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $summary = $this->attendance->monthlySummary(
            $company->id,
            $year,
            $month,
            $request->filled('employee_id') ? $request->integer('employee_id') : null
        );

        return view('hr.attendance.index', [
            'records' => Attendance::query()->forCompany($company->id)->whereRaw('1=0')->paginate(1),
            'summary' => $summary,
            'view' => 'summary',
            'year' => $year,
            'month' => $month,
            'statuses' => Attendance::STATUSES,
            'employees' => Employee::query()->forCompany($company->id)->where('status', 'active')->orderBy('last_name')->get(),
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'filters' => array_merge($request->only(['employee_id', 'year', 'month']), ['view' => 'summary']),
        ]);
    }

    protected function ensureEmployeeCompany(int $employeeId): void
    {
        $ok = Employee::query()
            ->forCompany(Workspace::company()->id)
            ->whereKey($employeeId)
            ->exists();

        if (! $ok) {
            abort(404);
        }
    }
}
