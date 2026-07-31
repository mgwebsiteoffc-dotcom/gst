@extends('layouts.marketing')
@section('title', 'IFSC Code Lookup - Free Bank Branch Finder')
@section('content')
<div class="bg-gradient-to-br from-blue-50 via-white to-cyan-50 min-h-screen">
    <div class="container mx-auto px-4 py-12 max-w-4xl">
        
        {{-- Breadcrumb --}}
        <nav class="mb-6 text-sm text-slate-600">
            <a href="{{ route('home') }}" class="hover:text-blue-600">Home</a>
            <span class="mx-2">/</span>
            <a href="{{ route('tools') }}" class="hover:text-blue-600">Tools</a>
            <span class="mx-2">/</span>
            <span class="text-slate-900">IFSC Code Lookup</span>
        </nav>
        {{-- Header --}}
        <div class="text-center mb-10">
            <h1 class="text-4xl font-bold text-slate-900 mb-4">IFSC Code Lookup</h1>
            <p class="text-lg text-slate-600">Find bank branch details using IFSC code. Live data from RBI database.</p>
        </div>
        {{-- Search Card --}}
        <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">IFSC Code</label>
                <input type="text" id="ifscCode" placeholder="SBIN0001234" maxlength="11"
                       class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 uppercase font-mono text-lg">
                <p class="mt-2 text-xs text-slate-500">Enter 11-character IFSC code (e.g., SBIN0001234, HDFC0000123)</p>
            </div>
            <button id="searchBtn" onclick="lookupIfsc()" 
                    class="mt-6 w-full bg-gradient-to-r from-blue-600 to-cyan-600 text-white font-semibold py-3 px-6 rounded-lg hover:from-blue-700 hover:to-cyan-700 transition-all shadow-lg">
                <span id="btnText">Lookup IFSC</span>
                <span id="btnLoader" class="hidden">
                    <svg class="animate-spin inline h-5 w-5 ml-2" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </span>
            </button>
        </div>
        {{-- Results --}}
        <div id="results" class="hidden">
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-slate-900">Branch Details</h2>
                    <span class="px-4 py-2 rounded-full text-sm font-semibold bg-emerald-100 text-emerald-700">
                        ✓ Valid IFSC
                    </span>
                </div>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div class="border-b border-slate-200 pb-3">
                            <div class="text-xs text-slate-500 mb-1">Bank Name</div>
                            <div id="bankName" class="text-lg font-semibold text-slate-900"></div>
                        </div>
                        <div class="border-b border-slate-200 pb-3">
                            <div class="text-xs text-slate-500 mb-1">IFSC Code</div>
                            <div id="ifscResult" class="text-lg font-mono font-semibold text-slate-900"></div>
                        </div>
                        <div class="border-b border-slate-200 pb-3">
                            <div class="text-xs text-slate-500 mb-1">Branch Name</div>
                            <div id="branchName" class="text-lg font-semibold text-slate-900"></div>
                        </div>
                        <div class="border-b border-slate-200 pb-3">
                            <div class="text-xs text-slate-500 mb-1">MICR Code</div>
                            <div id="micrCode" class="text-lg font-mono font-semibold text-slate-900"></div>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="border-b border-slate-200 pb-3">
                            <div class="text-xs text-slate-500 mb-1">Address</div>
                            <div id="address" class="text-sm text-slate-700"></div>
                        </div>
                        <div class="border-b border-slate-200 pb-3">
                            <div class="text-xs text-slate-500 mb-1">City</div>
                            <div id="city" class="text-lg text-slate-900"></div>
                        </div>
                        <div class="border-b border-slate-200 pb-3">
                            <div class="text-xs text-slate-500 mb-1">District</div>
                            <div id="district" class="text-lg text-slate-900"></div>
                        </div>
                        <div class="border-b border-slate-200 pb-3">
                            <div class="text-xs text-slate-500 mb-1">State</div>
                            <div id="state" class="text-lg text-slate-900"></div>
                        </div>
                    </div>
                </div>
                <div class="mt-6 pt-6 border-t border-slate-200">
                    <button onclick="copyIfsc()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
                        📋 Copy IFSC Code
                    </button>
                </div>
            </div>
        </div>
        {{-- Error --}}
        <div id="error" class="hidden mt-6 bg-red-50 border border-red-200 rounded-lg p-4">
            <p id="errorText" class="text-sm text-red-800"></p>
        </div>
        {{-- Info Section --}}
        <div class="mt-12 bg-white rounded-2xl shadow-lg p-8">
            <h2 class="text-2xl font-bold text-slate-900 mb-4">About IFSC Codes</h2>
            <div class="prose prose-slate max-w-none">
                <p>IFSC (Indian Financial System Code) is an 11-character alphanumeric code that uniquely identifies each bank branch in India. It's essential for electronic fund transfers like NEFT, RTGS, and IMPS.</p>
                
                <h3 class="text-lg font-semibold text-slate-900 mt-6">IFSC Code Structure:</h3>
                <ul class="list-disc pl-6 space-y-2">
                    <li><strong>First 4 characters:</strong> Bank code (e.g., SBIN for State Bank of India)</li>
                    <li><strong>5th character:</strong> Always zero (0)</li>
                    <li><strong>Last 6 characters:</strong> Branch code (unique to each branch)</li>
                </ul>
                <h3 class="text-lg font-semibold text-slate-900 mt-6">Where is IFSC used?</h3>
                <ul class="list-disc pl-6 space-y-2">
                    <li>NEFT (National Electronic Funds Transfer)</li>
                    <li>RTGS (Real Time Gross Settlement)</li>
                    <li>IMPS (Immediate Payment Service)</li>
                    <li>UPI transactions (when adding bank account)</li>
                </ul>
                <h3 class="text-lg font-semibold text-slate-900 mt-6">Data Source:</h3>
                <p>This tool uses the <a href="https://razorpay.com/ifsc/" target="_blank" class="text-blue-600 hover:underline">Razorpay IFSC API</a>, which sources data directly from RBI's official database. Results are cached for 7 days for faster lookups.</p>
            </div>
        </div>
    </div>
</div>
<script>
let currentIfsc = '';
async function lookupIfsc() {
    const ifsc = document.getElementById('ifscCode').value.trim().toUpperCase();
    
    if (!ifsc) {
        showError('Please enter an IFSC code');
        return;
    }
    
    if (ifsc.length !== 11) {
        showError('IFSC code must be exactly 11 characters');
        return;
    }
    
    // Validate format: 4 letters + 0 + 6 alphanumeric
    if (!/^[A-Z]{4}0[A-Z0-9]{6}$/.test(ifsc)) {
        showError('Invalid IFSC format. Expected: 4 letters + 0 + 6 alphanumeric characters');
        return;
    }
    
    // Show loading
    document.getElementById('btnText').textContent = 'Looking up...';
    document.getElementById('btnLoader').classList.remove('hidden');
    document.getElementById('searchBtn').disabled = true;
    hideResults();
    hideError();
    
    try {
        const response = await fetch('/api/tools/ifsc-lookup', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ ifsc })
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.error || 'Lookup failed');
        }
        
        displayResults(data.data);
        currentIfsc = ifsc;
    } catch (error) {
        showError(error.message);
    } finally {
        document.getElementById('btnText').textContent = 'Lookup IFSC';
        document.getElementById('btnLoader').classList.add('hidden');
        document.getElementById('searchBtn').disabled = false;
    }
}
function displayResults(data) {
    document.getElementById('bankName').textContent = data.BANK;
    document.getElementById('ifscResult').textContent = data.IFSC;
    document.getElementById('branchName').textContent = data.BRANCH;
    document.getElementById('micrCode').textContent = data.MICR || 'N/A';
    document.getElementById('address').textContent = data.ADDRESS;
    document.getElementById('city').textContent = data.CITY;
    document.getElementById('district').textContent = data.DISTRICT;
    document.getElementById('state').textContent = data.STATE;
    
    document.getElementById('results').classList.remove('hidden');
}
function copyIfsc() {
    navigator.clipboard.writeText(currentIfsc).then(() => {
        const btn = event.target;
        const originalText = btn.textContent;
        btn.textContent = '✓ Copied!';
        setTimeout(() => {
            btn.textContent = originalText;
        }, 2000);
    });
}
function hideResults() {
    document.getElementById('results').classList.add('hidden');
}
function showError(message) {
    const errorDiv = document.getElementById('error');
    document.getElementById('errorText').textContent = message;
    errorDiv.classList.remove('hidden');
}
function hideError() {
    document.getElementById('error').classList.add('hidden');
}
// Allow Enter key to trigger search
document.getElementById('ifscCode').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        lookupIfsc();
    }
});
// Auto-uppercase input
document.getElementById('ifscCode').addEventListener('input', function(e) {
    e.target.value = e.target.value.toUpperCase();
});
</script>
@endsection

