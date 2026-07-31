@extends('layouts.marketing')
@section('title', 'GSTIN Search by PAN or Name - Free GST Tool')
@section('content')
<div class="bg-gradient-to-br from-indigo-50 via-white to-purple-50 min-h-screen">
    <div class="container mx-auto px-4 py-12 max-w-4xl">
        
        {{-- Breadcrumb --}}
        <nav class="mb-6 text-sm text-slate-600">
            <a href="{{ route('home') }}" class="hover:text-indigo-600">Home</a>
            <span class="mx-2">/</span>
            <a href="{{ route('tools') }}" class="hover:text-indigo-600">Tools</a>
            <span class="mx-2">/</span>
            <span class="text-slate-900">GSTIN Search</span>
        </nav>
        {{-- Header --}}
        <div class="text-center mb-10">
            <h1 class="text-4xl font-bold text-slate-900 mb-4">GSTIN Search by PAN or Name</h1>
            <p class="text-lg text-slate-600">Search for GST registrations using PAN number or business name. Find all GSTINs linked to a PAN across India.</p>
        </div>
        {{-- Search Card --}}
        <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Search By</label>
                    <select id="searchMode" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="pan">PAN Number</option>
                        <option value="name">Business Name</option>
                    </select>
                </div>
                <div>
                    <label id="queryLabel" class="block text-sm font-medium text-slate-700 mb-2">PAN Number</label>
                    <input type="text" id="searchQuery" placeholder="ABCDE1234F" 
                           class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 uppercase">
                </div>
            </div>
            <button id="searchBtn" onclick="searchGstin()" 
                    class="mt-6 w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold py-3 px-6 rounded-lg hover:from-indigo-700 hover:to-purple-700 transition-all shadow-lg">
                <span id="btnText">Search GSTIN</span>
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
            <div id="demoBanner" class="hidden mb-4 bg-amber-50 border border-amber-200 rounded-lg p-4">
                <p class="text-sm text-amber-800">
                    <strong>Demo Mode:</strong> Showing sample data. To enable live search, configure your API key in <code>.env</code> file.
                </p>
            </div>
            
            <div id="resultsList" class="space-y-4"></div>
        </div>
        {{-- Error --}}
        <div id="error" class="hidden mt-6 bg-red-50 border border-red-200 rounded-lg p-4">
            <p id="errorText" class="text-sm text-red-800"></p>
        </div>
        {{-- Info Section --}}
        <div class="mt-12 bg-white rounded-2xl shadow-lg p-8">
            <h2 class="text-2xl font-bold text-slate-900 mb-4">About GSTIN Search</h2>
            <div class="prose prose-slate max-w-none">
                <p>A GSTIN (Goods and Services Tax Identification Number) is a 15-digit unique code assigned to every registered business in India. One PAN can have multiple GSTINs if the business operates in multiple states.</p>
                
                <h3 class="text-lg font-semibold text-slate-900 mt-6">What you can find:</h3>
                <ul class="list-disc pl-6 space-y-2">
                    <li>All GST registrations linked to a PAN</li>
                    <li>Legal name and trade name of the business</li>
                    <li>State and registration date</li>
                    <li>Current status (Active, Cancelled, Suspended)</li>
                    <li>Type of registration (Regular, Composition, etc.)</li>
                </ul>
                <h3 class="text-lg font-semibold text-slate-900 mt-6">API Integration:</h3>
                <p>To enable live search, sign up with a GST API provider and add your API key to the <code>.env</code> file:</p>
                <pre class="bg-slate-100 p-4 rounded-lg text-sm overflow-x-auto">GSTIN_API_KEY=your_api_key_here
GSTIN_API_PROVIDER=masters_india</pre>
                
                <p class="mt-4">Supported providers: Masters India, ClearTax, Karza, Signzy</p>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById('searchMode').addEventListener('change', function() {
    const mode = this.value;
    const label = document.getElementById('queryLabel');
    const input = document.getElementById('searchQuery');
    
    if (mode === 'pan') {
        label.textContent = 'PAN Number';
        input.placeholder = 'ABCDE1234F';
        input.maxLength = 10;
    } else {
        label.textContent = 'Business Name';
        input.placeholder = 'Reliance Industries';
        input.maxLength = 100;
    }
});
async function searchGstin() {
    const mode = document.getElementById('searchMode').value;
    const query = document.getElementById('searchQuery').value.trim();
    
    if (!query) {
        showError('Please enter a ' + (mode === 'pan' ? 'PAN number' : 'business name'));
        return;
    }
    
    if (mode === 'pan' && query.length !== 10) {
        showError('PAN must be exactly 10 characters');
        return;
    }
    
    // Show loading
    document.getElementById('btnText').textContent = 'Searching...';
    document.getElementById('btnLoader').classList.remove('hidden');
    document.getElementById('searchBtn').disabled = true;
    hideResults();
    hideError();
    
    try {
        const response = await fetch('/api/tools/gstin-search', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ mode, query })
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.error || 'Search failed');
        }
        
        displayResults(data);
    } catch (error) {
        showError(error.message);
    } finally {
        document.getElementById('btnText').textContent = 'Search GSTIN';
        document.getElementById('btnLoader').classList.add('hidden');
        document.getElementById('searchBtn').disabled = false;
    }
}
function displayResults(data) {
    const container = document.getElementById('results');
    const list = document.getElementById('resultsList');
    const demoBanner = document.getElementById('demoBanner');
    
    // Show demo banner if applicable
    if (data.demo) {
        demoBanner.classList.remove('hidden');
    } else {
        demoBanner.classList.add('hidden');
    }
    
    // Clear previous results
    list.innerHTML = '';
    
    // Display results
    if (data.data.results && data.data.results.length > 0) {
        data.data.results.forEach(gstin => {
            const card = document.createElement('div');
            card.className = 'bg-white rounded-xl shadow-md p-6 border border-slate-200 hover:shadow-lg transition-shadow';
            
            const statusColor = gstin.status === 'Active' ? 'emerald' : 
                              gstin.status === 'Cancelled' ? 'red' : 'amber';
            
            card.innerHTML = `
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900">${gstin.legal_name}</h3>
                        <p class="text-sm text-slate-600 mt-1">${gstin.trade_name || 'N/A'}</p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-${statusColor}-100 text-${statusColor}-700">
                        ${gstin.status}
                    </span>
                </div>
                <div class="grid md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-slate-500">GSTIN:</span>
                        <span class="ml-2 font-mono font-semibold text-slate-900">${gstin.gstin}</span>
                    </div>
                    <div>
                        <span class="text-slate-500">State:</span>
                        <span class="ml-2 text-slate-900">${gstin.state} (${gstin.state_code})</span>
                    </div>
                    <div>
                        <span class="text-slate-500">Registration Date:</span>
                        <span class="ml-2 text-slate-900">${gstin.registration_date}</span>
                    </div>
                </div>
            `;
            
            list.appendChild(card);
        });
        
        container.classList.remove('hidden');
    } else {
        showError('No GST registrations found');
    }
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
document.getElementById('searchQuery').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchGstin();
    }
});
</script>
@endsection