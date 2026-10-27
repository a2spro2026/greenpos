<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use App\Services\EmployeeService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function __construct(private EmployeeService $employees)
    {
    }

    public function dashboard(): View
    {
        $this->authorize('hr.view');
        $company = Workspace::company();
        $base = Employee::query()->forCompany($company->id);

        $stats = [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('status', 'active')->count(),
            'inactive' => (clone $base)->where('status', 'inactive')->count(),
            'suspended' => (clone $base)->where('status', 'suspended')->count(),
            'new' => (clone $base)->where('created_at', '>=', now()->subDays(30))->count(),
            'linked' => (clone $base)->whereNotNull('user_id')->count(),
        ];

        $byRole = collect(Employee::JOB_ROLES)->map(fn ($label, $key) => [
            'label' => $label,
            'count' => (clone $base)->where('job_role', $key)->count(),
        ])->values();

        $recent = Employee::query()->forCompany($company->id)->latest()->limit(8)->get();

        return view('hr.dashboard', compact('stats', 'byRole', 'recent'));
    }

    public function index(Request $request): View
    {
        $this->authorize('hr.view');
        $company = Workspace::company();

        $query = Employee::query()
            ->forCompany($company->id)
            ->with(['store', 'user'])
            ->search($request->string('q')->toString())
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('job_role'), fn ($q) => $q->where('job_role', $request->string('job_role')))
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->integer('store_id')));

        $sort = $request->string('sort', 'last_name')->toString();
        $dir = $request->string('dir', 'asc')->toString() === 'desc' ? 'desc' : 'asc';
        $allowed = ['last_name', 'first_name', 'code', 'status', 'job_role', 'hire_date', 'created_at'];
        if (! in_array($sort, $allowed, true)) {
            $sort = 'last_name';
        }
        $query->orderBy($sort, $dir);

        return view('hr.employees.index', [
            'employees' => $query->paginate(15)->withQueryString(),
            'statuses' => Employee::STATUSES,
            'jobRoles' => Employee::JOB_ROLES,
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'filters' => $request->only(['q', 'status', 'job_role', 'store_id', 'sort', 'dir']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('hr.create');
        $company = Workspace::company();

        return view('hr.employees.create', [
            'statuses' => Employee::STATUSES,
            'jobRoles' => Employee::JOB_ROLES,
            'payTypes' => Employee::PAY_TYPES,
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'users' => User::query()->whereHas('companies', fn ($q) => $q->where('companies.id', $company->id))->orderBy('name')->get(),
            'suggestedCode' => $this->employees->nextCode($company->id),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('hr.create');
        $data = $this->validated($request);
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo');
        }

        $docs = [];
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                if ($file) {
                    $docs[] = ['file' => $file];
                }
            }
        }

        $employee = $this->employees->create($data, $docs);

        return redirect()->route('hr.employees.show', $employee)->with('success', 'Employé créé.');
    }

    public function show(Employee $employee): View
    {
        $this->authorize('hr.view');
        $this->ensureCompany($employee);
        $employee->load(['store', 'user', 'documents.uploader', 'creator']);

        $salesCount = 0;
        $salesTotal = 0.0;
        if ($employee->user_id) {
            $salesQuery = Sale::query()
                ->where('company_id', $employee->company_id)
                ->where('salesperson_id', $employee->user_id);
            $salesCount = (clone $salesQuery)->count();
            $salesTotal = (float) (clone $salesQuery)->sum('total_ttc');
        }

        $performance = [
            'sales_count' => $salesCount,
            'sales_total' => $salesTotal,
            'attendance_month' => $employee->attendances()
                ->whereBetween('date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                ->count(),
            'payrolls' => $employee->payrolls()->latest('year')->latest('month')->limit(6)->get(),
        ];

        return view('hr.employees.show', compact('employee', 'performance'));
    }

    public function edit(Employee $employee): View
    {
        $this->authorize('hr.update');
        $this->ensureCompany($employee);
        $company = Workspace::company();

        return view('hr.employees.edit', [
            'employee' => $employee,
            'statuses' => Employee::STATUSES,
            'jobRoles' => Employee::JOB_ROLES,
            'payTypes' => Employee::PAY_TYPES,
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'users' => User::query()->whereHas('companies', fn ($q) => $q->where('companies.id', $company->id))->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('hr.update');
        $this->ensureCompany($employee);
        $data = $this->validated($request, $employee);
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo');
        }
        $this->employees->update($employee, $data);

        return redirect()->route('hr.employees.show', $employee)->with('success', 'Employé mis à jour.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $this->authorize('hr.delete');
        $this->ensureCompany($employee);
        $this->employees->deactivate($employee);

        return redirect()->route('hr.employees.index')->with('success', 'Employé désactivé.');
    }

    public function storeDocument(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('hr.update');
        $this->ensureCompany($employee);
        $request->validate([
            'document' => ['required', 'file', 'max:10240'],
            'title' => ['nullable', 'string', 'max:160'],
            'category' => ['nullable', 'string', 'max:64'],
        ]);

        $this->employees->storeDocument(
            $employee,
            $request->file('document'),
            $request->input('title'),
            $request->input('category', 'other')
        );

        return back()->with('success', 'Document ajouté.');
    }

    public function destroyDocument(Employee $employee, EmployeeDocument $document): RedirectResponse
    {
        $this->authorize('hr.update');
        $this->ensureCompany($employee);
        if ($document->employee_id !== $employee->id) {
            abort(404);
        }
        $this->employees->deleteDocument($document);

        return back()->with('success', 'Document supprimé.');
    }

    protected function validated(Request $request, ?Employee $employee = null): array
    {
        $companyId = Workspace::company()->id;

        return $request->validate([
            'code' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique('employees', 'code')
                    ->where(fn ($q) => $q->where('company_id', $companyId)->whereNull('deleted_at'))
                    ->ignore($employee?->id),
            ],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'job_role' => ['required', 'in:admin,manager,cashier,vendor,other'],
            'status' => ['required', 'in:active,inactive,suspended'],
            'store_id' => ['nullable', 'exists:stores,id'],
            'user_id' => ['nullable', 'exists:users,id'],
            'hire_date' => ['nullable', 'date'],
            'bank_iban' => ['nullable', 'string', 'max:64'],
            'bank_name' => ['nullable', 'string', 'max:160'],
            'commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'commission_fixed' => ['nullable', 'numeric', 'min:0'],
            'pay_type' => ['required', 'in:hourly,daily,weekly,monthly'],
            'base_rate' => ['nullable', 'numeric', 'min:0'],
            'overtime_multiplier' => ['nullable', 'numeric', 'min:0'],
            'normal_hours_per_day' => ['nullable', 'numeric', 'min:0'],
            'rest_day' => ['nullable', 'string', 'max:32'],
            'absence_penalty' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'max:4096'],
            'documents.*' => ['nullable', 'file', 'max:10240'],
        ]);
    }

    protected function ensureCompany(Employee $employee): void
    {
        if ($employee->company_id !== Workspace::company()?->id) {
            abort(404);
        }
    }
}
