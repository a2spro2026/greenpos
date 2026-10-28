<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Store;
use App\Models\Supplier;
use App\Services\ExpenseService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function __construct(private ExpenseService $expenses)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('accounting.view');
        $company = Workspace::company();

        $expenses = Expense::query()
            ->forCompany($company->id)
            ->with(['store', 'supplier', 'creator'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('label', 'like', $term)->orWhere('notes', 'like', $term);
                });
            })
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->filled('expense_type'), fn ($q) => $q->where('expense_type', $request->string('expense_type')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('expense_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('expense_date', '<=', $request->date('to')))
            ->latest('expense_date')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => (float) Expense::query()->forCompany($company->id)->sum('amount'),
            'fixed' => (float) Expense::query()->forCompany($company->id)->where('expense_type', 'fixed')->sum('amount'),
            'variable' => (float) Expense::query()->forCompany($company->id)->where('expense_type', 'variable')->sum('amount'),
            'month' => (float) Expense::query()->forCompany($company->id)->whereBetween('expense_date', [now()->copy()->startOfMonth(), now()->copy()->endOfMonth()])->sum('amount'),
        ];

        return view('accounting.expenses.index', [
            'expenses' => $expenses,
            'stats' => $stats,
            'categories' => Expense::CATEGORIES,
            'types' => Expense::TYPES,
            'filters' => $request->only(['q', 'category', 'expense_type', 'from', 'to']),
            'currency' => $company->currency ?? 'MAD',
        ]);
    }

    public function create(): View
    {
        $this->authorize('accounting.create');
        $company = Workspace::company();

        return view('accounting.expenses.create', [
            'categories' => Expense::CATEGORIES,
            'types' => Expense::TYPES,
            'recurrences' => Expense::RECURRENCES,
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'suppliers' => Supplier::query()->forCompany($company->id)->orderBy('name')->get(),
            'currency' => $company->currency ?? 'MAD',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('accounting.create');
        $expense = $this->expenses->create($this->validated($request), ! $request->boolean('skip_journal'));

        return redirect()->route('accounting.expenses.show', $expense)->with('success', 'Dépense enregistrée.');
    }

    public function show(Expense $expense): View
    {
        $this->authorize('accounting.view');
        $this->ensureCompany($expense);
        $expense->load(['store', 'supplier', 'creator']);

        return view('accounting.expenses.show', compact('expense'));
    }

    public function edit(Expense $expense): View
    {
        $this->authorize('accounting.update');
        $this->ensureCompany($expense);
        $company = Workspace::company();

        return view('accounting.expenses.edit', [
            'expense' => $expense,
            'categories' => Expense::CATEGORIES,
            'types' => Expense::TYPES,
            'recurrences' => Expense::RECURRENCES,
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'suppliers' => Supplier::query()->forCompany($company->id)->orderBy('name')->get(),
            'currency' => $expense->currency ?: ($company->currency ?? 'MAD'),
        ]);
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $this->authorize('accounting.update');
        $this->ensureCompany($expense);
        $this->expenses->update($expense, $this->validated($request));

        return redirect()->route('accounting.expenses.show', $expense)->with('success', 'Dépense mise à jour.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $this->authorize('accounting.delete');
        $this->ensureCompany($expense);
        $this->expenses->softDelete($expense);

        return redirect()->route('accounting.expenses.index')->with('success', 'Dépense archivée.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:200'],
            'category' => ['nullable', 'string', 'max:64'],
            'expense_type' => ['required', 'in:fixed,variable'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'max:8'],
            'expense_date' => ['required', 'date'],
            'store_id' => ['nullable', 'exists:stores,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'payment_method' => ['nullable', 'string', 'max:32'],
            'is_recurring' => ['nullable', 'boolean'],
            'recurrence' => ['nullable', 'in:monthly,quarterly,semiannual,annual'],
            'next_due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    protected function ensureCompany(Expense $expense): void
    {
        if ($expense->company_id !== Workspace::company()?->id) {
            abort(404);
        }
    }
}
