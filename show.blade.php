<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Investment Details') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Investment Header -->
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">{{ $investment->investmentPlan->name }}</h3>
                            <p class="text-gray-600">Investment ID: #{{ $investment->id }}</p>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                @if($investment->status === 'active') bg-green-100 text-green-800
                                @elseif($investment->status === 'matured') bg-blue-100 text-blue-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ ucfirst($investment->status) }}
                            </span>
                        </div>
                    </div>

                    <!-- Investment Summary -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-blue-700 mb-1">Investment Amount</h4>
                            <p class="text-2xl font-bold text-blue-900">₦{{ number_format($investment->amount, 2) }}</p>
                        </div>
                        
                        <div class="bg-green-50 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-green-700 mb-1">Expected Profit</h4>
                            <p class="text-2xl font-bold text-green-900">₦{{ number_format($investment->profit_amount, 2) }}</p>
                        </div>
                        
                        <div class="bg-purple-50 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-purple-700 mb-1">Total Return</h4>
                            <p class="text-2xl font-bold text-purple-900">₦{{ number_format($investment->total_return, 2) }}</p>
                        </div>
                        
                        <div class="bg-orange-50 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-orange-700 mb-1">Days Remaining</h4>
                            <p class="text-2xl font-bold text-orange-900">
                                @if($investment->status === 'active')
                                    {{ $investment->days_remaining }}
                                @else
                                    -
                                @endif
                            </p>
                        </div>
                    </div>

                    <!-- Investment Details -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Plan Information -->
                        <div class="bg-gray-50 p-6 rounded-lg">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">Plan Information</h4>
                            
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Plan Name:</span>
                                    <span class="font-medium">{{ $investment->investmentPlan->name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Profit Rate:</span>
                                    <span class="font-medium">{{ $investment->investmentPlan->profit_percentage }}%</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Duration:</span>
                                    <span class="font-medium">{{ $investment->investmentPlan->duration_days }} days</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Investment Date:</span>
                                    <span class="font-medium">{{ $investment->created_at->format('M d, Y H:i') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Maturity Date:</span>
                                    <span class="font-medium">{{ $investment->maturity_date->format('M d, Y H:i') }}</span>
                                </div>
                                @if($investment->credited_at)
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Credited Date:</span>
                                        <span class="font-medium text-green-600">{{ $investment->credited_at->format('M d, Y H:i') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Progress Tracker -->
                        <div class="bg-gray-50 p-6 rounded-lg">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">Investment Progress</h4>
                            
                            @if($investment->status === 'active')
                                @php
                                    $totalDays = $investment->investmentPlan->duration_days;
                                    $daysElapsed = $totalDays - $investment->days_remaining;
                                    $progressPercentage = ($daysElapsed / $totalDays) * 100;
                                @endphp
                                
                                <div class="mb-4">
                                    <div class="flex justify-between text-sm text-gray-600 mb-2">
                                        <span>Progress</span>
                                        <span>{{ round($progressPercentage, 1) }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                                             style="width: {{ $progressPercentage }}%"></div>
                                    </div>
                                </div>
                                
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Days Elapsed:</span>
                                        <span class="font-medium">{{ $daysElapsed }} / {{ $totalDays }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Days Remaining:</span>
                                        <span class="font-medium">{{ $investment->days_remaining }}</span>
                                    </div>
                                </div>
                                
                                @if($investment->days_remaining <= 1)
                                    <div class="mt-4 p-3 bg-yellow-100 border border-yellow-300 rounded-lg">
                                        <p class="text-sm text-yellow-800">
                                            <strong>Almost there!</strong> Your investment will mature soon and profits will be automatically credited to your account.
                                        </p>
                                    </div>
                                @endif
                            @elseif($investment->status === 'matured')
                                <div class="text-center py-4">
                                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    <h5 class="text-lg font-medium text-green-800 mb-1">Investment Matured!</h5>
                                    <p class="text-sm text-green-600">Your profits have been credited to your account.</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-8 flex justify-between items-center">
                        <a href="{{ route('investments.index') }}" 
                           class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium py-2 px-6 rounded-lg transition-colors">
                            ← Back to Investments
                        </a>
                        
                        @if($investment->status === 'matured')
                            <a href="{{ route('investments.index') }}" 
                               class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition-colors">
                                Make New Investment
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

