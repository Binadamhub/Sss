<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvestmentPlan;
use App\Models\Investment;
use Illuminate\Http\Request;

class AdminInvestmentController extends Controller
{
    /**
     * Display investment plans.
     */
    public function index()
    {
        $plans = InvestmentPlan::withCount(['investments'])
            ->withSum('investments', 'amount')
            ->get();

        return view('admin.investments.index', compact('plans'));
    }

    /**
     * Show create investment plan form.
     */
    public function create()
    {
        return view('admin.investments.create');
    }

    /**
     * Store new investment plan.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:investment_plans',
            'description' => 'nullable|string|max:1000',
            'minimum_amount' => 'required|numeric|min:1',
            'maximum_amount' => 'nullable|numeric|gt:minimum_amount',
            'profit_percentage' => 'required|numeric|min:0|max:1000',
            'duration_days' => 'required|integer|min:1|max:365',
            'is_active' => 'boolean',
        ]);

        InvestmentPlan::create($request->all());

        return redirect()->route('admin.investments.index')
            ->with('success', 'Investment plan created successfully.');
    }

    /**
     * Show investment plan details.
     */
    public function show(InvestmentPlan $investment)
    {
        $investment->load(['investments.user']);
        
        $stats = [
            'total_investments' => $investment->investments->count(),
            'active_investments' => $investment->investments->where('status', 'active')->count(),
            'matured_investments' => $investment->investments->where('status', 'matured')->count(),
            'total_amount' => $investment->investments->sum('amount'),
            'total_profits_paid' => $investment->investments->where('status', 'matured')->sum('profit_amount'),
        ];

        $recentInvestments = $investment->investments()
            ->with('user')
            ->latest()
            ->take(10)
            ->get();

        return view('admin.investments.show', compact('investment', 'stats', 'recentInvestments'));
    }

    /**
     * Show edit investment plan form.
     */
    public function edit(InvestmentPlan $investment)
    {
        return view('admin.investments.edit', compact('investment'));
    }

    /**
     * Update investment plan.
     */
    public function update(Request $request, InvestmentPlan $investment)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:investment_plans,name,' . $investment->id,
            'description' => 'nullable|string|max:1000',
            'minimum_amount' => 'required|numeric|min:1',
            'maximum_amount' => 'nullable|numeric|gt:minimum_amount',
            'profit_percentage' => 'required|numeric|min:0|max:1000',
            'duration_days' => 'required|integer|min:1|max:365',
            'is_active' => 'boolean',
        ]);

        $investment->update($request->all());

        return redirect()->route('admin.investments.show', $investment)
            ->with('success', 'Investment plan updated successfully.');
    }

    /**
     * Toggle investment plan status.
     */
    public function toggleStatus(InvestmentPlan $investment)
    {
        $investment->update(['is_active' => !$investment->is_active]);

        $status = $investment->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Investment plan has been {$status} successfully.");
    }

    /**
     * Delete investment plan.
     */
    public function destroy(InvestmentPlan $investment)
    {
        // Check if plan has active investments
        if ($investment->investments()->where('status', 'active')->exists()) {
            return back()->with('error', 'Cannot delete plan with active investments.');
        }

        $investment->delete();

        return redirect()->route('admin.investments.index')
            ->with('success', 'Investment plan deleted successfully.');
    }

    /**
     * View all investments.
     */
    public function investments(Request $request)
    {
        $query = Investment::with(['user', 'investmentPlan']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by plan
        if ($request->filled('plan')) {
            $query->where('investment_plan_id', $request->plan);
        }

        // Search by user
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $investments = $query->paginate(20);
        $plans = InvestmentPlan::all();

        // Statistics
        $stats = [
            'active' => Investment::where('status', 'active')->count(),
            'matured' => Investment::where('status', 'matured')->count(),
            'total_amount' => Investment::sum('amount'),
            'total_profits' => Investment::where('status', 'matured')->sum('profit_amount'),
        ];

        return view('admin.investments.investments', compact('investments', 'plans', 'stats'));
    }
}

