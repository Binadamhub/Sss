<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('New Investment - ' . $plan->name) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Plan Details -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                        <h3 class="text-xl font-bold text-blue-900 mb-4">{{ $plan->name }} Details</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <span class="text-blue-700 font-medium">Profit Rate:</span>
                                <span class="text-blue-900 font-bold">{{ $plan->profit_percentage }}%</span>
                            </div>
                            <div>
                                <span class="text-blue-700 font-medium">Duration:</span>
                                <span class="text-blue-900 font-bold">{{ $plan->duration_days }} days</span>
                            </div>
                            <div>
                                <span class="text-blue-700 font-medium">Minimum:</span>
                                <span class="text-blue-900 font-bold">₦{{ number_format($plan->minimum_amount, 2) }}</span>
                            </div>
                            <div>
                                <span class="text-blue-700 font-medium">Maximum:</span>
                                <span class="text-blue-900 font-bold">
                                    @if($plan->maximum_amount)
                                        ₦{{ number_format($plan->maximum_amount, 2) }}
                                    @else
                                        No limit
                                    @endif
                                </span>
                            </div>
                        </div>

                        @if($plan->description)
                            <p class="text-blue-800 mt-4">{{ $plan->description }}</p>
                        @endif
                    </div>

                    <!-- Current Balance -->
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                            <span class="text-green-700 font-medium">Your Current Balance: </span>
                            <span class="text-green-900 font-bold">₦{{ number_format(auth()->user()->balance, 2) }}</span>
                        </div>
                    </div>

                    <!-- Investment Form -->
                    <form method="POST" action="{{ route('investments.store', $plan) }}" id="investmentForm">
                        @csrf

                        <div class="mb-6">
                            <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                                Investment Amount (₦)
                            </label>
                            <input type="number" 
                                   id="amount" 
                                   name="amount" 
                                   min="{{ $plan->minimum_amount }}"
                                   @if($plan->maximum_amount) max="{{ $plan->maximum_amount }}" @endif
                                   step="0.01"
                                   value="{{ old('amount') }}"
                                   class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Enter amount to invest"
                                   required>
                            @error('amount')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Investment Calculator -->
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-6" id="calculator">
                            <h4 class="font-medium text-gray-900 mb-4">Investment Calculator</h4>
                            
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Investment Amount:</span>
                                    <span class="font-medium" id="investmentAmount">₦0.00</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Profit ({{ $plan->profit_percentage }}%):</span>
                                    <span class="font-medium text-green-600" id="profitAmount">₦0.00</span>
                                </div>
                                <div class="flex justify-between border-t pt-3">
                                    <span class="text-gray-900 font-medium">Total Return:</span>
                                    <span class="font-bold text-green-600" id="totalReturn">₦0.00</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Maturity Date:</span>
                                    <span class="font-medium" id="maturityDate">-</span>
                                </div>
                            </div>
                        </div>

                        <!-- Terms and Conditions -->
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                            <div class="flex">
                                <svg class="w-5 h-5 text-yellow-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                <div class="text-sm text-yellow-800">
                                    <p class="font-medium mb-1">Important Notes:</p>
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>Your investment will be locked for {{ $plan->duration_days }} days</li>
                                        <li>Profits will be automatically credited to your account on maturity</li>
                                        <li>Early withdrawal is not permitted</li>
                                        <li>Ensure you have sufficient balance before investing</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex items-center justify-between">
                            <a href="{{ route('investments.index') }}" 
                               class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium py-2 px-6 rounded-lg transition-colors">
                                Cancel
                            </a>
                            
                            <button type="submit" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition-colors"
                                    id="submitBtn">
                                Confirm Investment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Investment Calculator
        const amountInput = document.getElementById('amount');
        const profitRate = {{ $plan->profit_percentage }};
        const durationDays = {{ $plan->duration_days }};
        const userBalance = {{ auth()->user()->balance }};
        const minAmount = {{ $plan->minimum_amount }};
        const maxAmount = {{ $plan->maximum_amount ?? 'null' }};

        function updateCalculator() {
            const amount = parseFloat(amountInput.value) || 0;
            const profit = (amount * profitRate) / 100;
            const total = amount + profit;
            
            document.getElementById('investmentAmount').textContent = '₦' + amount.toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('profitAmount').textContent = '₦' + profit.toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('totalReturn').textContent = '₦' + total.toLocaleString('en-US', {minimumFractionDigits: 2});
            
            // Calculate maturity date
            if (amount > 0) {
                const maturityDate = new Date();
                maturityDate.setDate(maturityDate.getDate() + durationDays);
                document.getElementById('maturityDate').textContent = maturityDate.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
            } else {
                document.getElementById('maturityDate').textContent = '-';
            }

            // Validate amount
            const submitBtn = document.getElementById('submitBtn');
            if (amount < minAmount || (maxAmount && amount > maxAmount) || amount > userBalance) {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }

        amountInput.addEventListener('input', updateCalculator);
        
        // Initialize calculator
        updateCalculator();
    </script>
</x-app-layout>

