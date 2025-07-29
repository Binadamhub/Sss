<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Investments') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Investment Plans -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">Available Investment Plans</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @foreach($investmentPlans as $plan)
                            <div class="border border-gray-200 rounded-lg p-6 hover:shadow-lg transition-shadow">
                                <div class="flex justify-between items-start mb-4">
                                    <h4 class="text-xl font-bold text-gray-900">{{ $plan->name }}</h4>
                                    <span class="bg-green-100 text-green-800 text-sm font-medium px-2.5 py-0.5 rounded">
                                        {{ $plan->profit_percentage }}% Profit
                                    </span>
                                </div>
                                
                                <div class="space-y-3 mb-6">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Duration:</span>
                                        <span class="font-medium">{{ $plan->duration_days }} days</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Minimum Amount:</span>
                                        <span class="font-medium">₦{{ number_format($plan->minimum_amount, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Maximum Amount:</span>
                                        <span class="font-medium">
                                            @if($plan->maximum_amount)
                                                ₦{{ number_format($plan->maximum_amount, 2) }}
                                            @else
                                                No limit
                                            @endif
                                        </span>
                                    </div>
                                </div>

                                @if($plan->description)
                                    <p class="text-gray-600 mb-4">{{ $plan->description }}</p>
                                @endif

                                <!-- Example Calculation -->
                                <div class="bg-gray-50 p-4 rounded-lg mb-4">
                                    <h5 class="font-medium text-gray-900 mb-2">Example:</h5>
                                    <div class="text-sm text-gray-600">
                                        <p>Investment: ₦{{ number_format($plan->minimum_amount, 2) }}</p>
                                        <p>Profit: ₦{{ number_format(($plan->minimum_amount * $plan->profit_percentage) / 100, 2) }}</p>
                                        <p class="font-medium text-green-600">
                                            Total Return: ₦{{ number_format($plan->calculateReturn($plan->minimum_amount), 2) }}
                                        </p>
                                    </div>
                                </div>

                                <a href="{{ route('investments.create', $plan) }}" 
                                   class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors inline-block text-center">
                                    Invest Now
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- User's Investments -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">Your Investments</h3>
                    
                    @if($userInvestments->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expected Return</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Maturity Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days Left</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($userInvestments as $investment)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $investment->investmentPlan->name }}</div>
                                                <div class="text-sm text-gray-500">{{ $investment->investmentPlan->profit_percentage }}% in {{ $investment->investmentPlan->duration_days }} days</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                ₦{{ number_format($investment->amount, 2) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                ₦{{ number_format($investment->total_return, 2) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                    @if($investment->status === 'active') bg-green-100 text-green-800
                                                    @elseif($investment->status === 'matured') bg-blue-100 text-blue-800
                                                    @else bg-gray-100 text-gray-800 @endif">
                                                    {{ ucfirst($investment->status) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $investment->maturity_date->format('M d, Y H:i') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                @if($investment->status === 'active')
                                                    {{ $investment->days_remaining }} days
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="{{ route('investments.show', $investment) }}" class="text-blue-600 hover:text-blue-900">
                                                    View Details
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $userInvestments->links() }}
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No investments yet</h3>
                            <p class="mt-1 text-sm text-gray-500">Get started by making your first investment.</p>
                            <div class="mt-6">
                                <a href="#" onclick="document.querySelector('.bg-white').scrollIntoView({behavior: 'smooth'})" 
                                   class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                    Choose a Plan
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

