<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ToolController extends Controller
{
    /**
     * GSTIN Search by PAN
     * Requires API key from: https://www.mastersindia.co/api/gstin-search-api/
     * or similar providers (ClearTax, Karza, etc.)
     */
    public function gstinSearch(Request $request)
    {
        $request->validate([
            'mode' => 'required|in:pan,name',
            'query' => 'required|string|min:3',
        ]);

        $apiKey = config('services.gstin_api.key');
        
        if (!$apiKey) {
            // Demo mode - return sample data
            return response()->json([
                'demo' => true,
                'data' => $this->getDemoGstinData($request->query, $request->mode)
            ]);
        }

        try {
            // Example integration with Masters India API
            // Replace with your actual API provider
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->get('https://api.mastersindia.co/v1/gstin/search', [
                'searchBy' => $request->mode,
                'value' => $request->query,
            ]);

            if ($response->successful()) {
                return response()->json([
                    'demo' => false,
                    'data' => $response->json()
                ]);
            }

            return response()->json([
                'error' => 'API request failed',
                'message' => $response->body()
            ], 500);

        } catch (\Exception $e) {
            Log::error('GSTIN Search API Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Service temporarily unavailable'
            ], 503);
        }
    }

    /**
     * GST Return Status Check
     * Requires API access to GST portal
     */
    public function gstReturnStatus(Request $request)
    {
        $request->validate([
            'gstin' => 'required|string|size:15',
            'financial_year' => 'required|string',
        ]);

        $apiKey = config('services.gstin_api.key');
        
        if (!$apiKey) {
            return response()->json([
                'demo' => true,
                'data' => $this->getDemoReturnStatus($request->gstin, $request->financial_year)
            ]);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->get('https://api.mastersindia.co/v1/return/status', [
                'gstin' => $request->gstin,
                'financial_year' => $request->financial_year,
            ]);

            return response()->json([
                'demo' => false,
                'data' => $response->json()
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Service unavailable'], 503);
        }
    }

    /**
     * E-Invoice Status Check
     */
    public function einvoiceStatus(Request $request)
    {
        $request->validate(['gstin' => 'required|string|size:15']);

        $apiKey = config('services.gstin_api.key');
        
        if (!$apiKey) {
            return response()->json([
                'demo' => true,
                'data' => $this->getDemoEinvoiceStatus($request->gstin)
            ]);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->get('https://api.mastersindia.co/v1/einvoice/status', [
                'gstin' => $request->gstin,
            ]);

            return response()->json([
                'demo' => false,
                'data' => $response->json()
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Service unavailable'], 503);
        }
    }

    /**
     * IFSC Code Lookup - Uses RBI's public API
     */
    public function ifscLookup(Request $request)
    {
        $request->validate(['ifsc' => 'required|string|size:11']);

        $ifsc = strtoupper($request->ifsc);
        $cacheKey = 'ifsc_' . $ifsc;

        // Cache for 7 days
        $data = Cache::remember($cacheKey, 604800, function () use ($ifsc) {
            // RBI's public IFSC API
            $response = Http::get("https://ifsc.razorpay.com/{$ifsc}");

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        });

        if ($data) {
            return response()->json(['success' => true, 'data' => $data]);
        }

        return response()->json(['error' => 'IFSC code not found'], 404);
    }

    /**
     * MCA Company Search
     * Requires API key from MCA or third-party provider
     */
    public function mcaSearch(Request $request)
    {
        $request->validate([
            'mode' => 'required|in:name,cin',
            'query' => 'required|string|min:3',
        ]);

        $apiKey = config('services.mca_api.key');
        
        if (!$apiKey) {
            return response()->json([
                'demo' => true,
                'data' => $this->getDemoMcaData($request->query, $request->mode)
            ]);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->get('https://api.example.com/mca/search', [
                'searchBy' => $request->mode,
                'value' => $request->query,
            ]);

            return response()->json([
                'demo' => false,
                'data' => $response->json()
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Service unavailable'], 503);
        }
    }

    /**
     * GST Case Laws Search
     */
    public function gstCaseLaws(Request $request)
    {
        $request->validate(['query' => 'required|string|min:3']);

        // For now, return curated case laws
        // In production, integrate with TaxGuru, SCC Online, or similar
        $caseLaws = $this->getCuratedCaseLaws($request->query);

        return response()->json(['data' => $caseLaws]);
    }

    /**
     * GST Filing Preference
     */
    public function gstFilingPreference(Request $request)
    {
        $request->validate(['gstin' => 'required|string|size:15']);

        $apiKey = config('services.gstin_api.key');
        
        if (!$apiKey) {
            return response()->json([
                'demo' => true,
                'data' => $this->getDemoFilingPreference($request->gstin)
            ]);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->get('https://api.mastersindia.co/v1/filing/preference', [
                'gstin' => $request->gstin,
            ]);

            return response()->json([
                'demo' => false,
                'data' => $response->json()
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Service unavailable'], 503);
        }
    }

    // Demo data methods
    private function getDemoGstinData($query, $mode)
    {
        return [
            'results' => [
                [
                    'gstin' => '27ABCDE1234F1Z5',
                    'legal_name' => 'SAMPLE COMPANY PVT LTD',
                    'trade_name' => 'Sample Business',
                    'state' => 'Maharashtra',
                    'state_code' => '27',
                    'status' => 'Active',
                    'registration_date' => '2017-07-01',
                ],
                [
                    'gstin' => '09ABCDE1234F2Z3',
                    'legal_name' => 'SAMPLE COMPANY PVT LTD',
                    'trade_name' => 'Sample Business UP',
                    'state' => 'Uttar Pradesh',
                    'state_code' => '09',
                    'status' => 'Active',
                    'registration_date' => '2018-04-01',
                ],
            ]
        ];
    }

    private function getDemoReturnStatus($gstin, $fy)
    {
        $months = ['Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'];
        $returns = [];

        foreach (['GSTR-1', 'GSTR-3B'] as $type) {
            $status = [];
            foreach ($months as $i => $month) {
                $status[$month] = $i < 6 ? 'Filed' : ($i < 8 ? 'Pending' : 'Not Due');
            }
            $returns[] = ['type' => $type, 'status' => $status];
        }

        return ['gstin' => $gstin, 'financial_year' => $fy, 'returns' => $returns];
    }

    private function getDemoEinvoiceStatus($gstin)
    {
        return [
            'gstin' => $gstin,
            'enabled' => true,
            'enabled_date' => '2024-04-01',
            'total_irns' => 1245,
            'last_irn_date' => '2026-07-28',
            'compliance_rate' => 98.5,
        ];
    }

    private function getDemoMcaData($query, $mode)
    {
        return [
            'company_name' => 'SAMPLE COMPANY PVT LTD',
            'cin' => 'U72200MH1995PLC092070',
            'registration_date' => '1995-03-15',
            'status' => 'Active',
            'class' => 'Private',
            'category' => 'Company limited by Shares',
            'authorized_capital' => 50000000,
            'paid_up_capital' => 35000000,
            'roc' => 'RoC Mumbai',
            'address' => 'Bandra Kurla Complex, Mumbai - 400051',
            'directors' => [
                ['name' => 'Rajesh Kumar', 'din' => '01234567'],
                ['name' => 'Priya Sharma', 'din' => '01234568'],
                ['name' => 'Anil Mehta', 'din' => '01234569'],
            ]
        ];
    }

    private function getDemoFilingPreference($gstin)
    {
        return [
            'gstin' => $gstin,
            'scheme' => 'QRMP',
            'period' => 'Quarterly',
            'gstr1_mode' => 'IFF for M1 & M2, Regular for M3',
            'gstr3b_mode' => 'Quarterly',
            'auto_draft_itc' => 'Enabled',
            'tax_liability_mode' => 'Self-assessed',
        ];
    }

    private function getCuratedCaseLaws($query)
    {
        $allCases = [
            [
                'title' => 'ITC on construction of immovable property',
                'section' => 'Section 17(5)(c)',
                'court' => 'Supreme Court',
                'year' => 2024,
                'summary' => 'ITC not available on goods/services used for construction of immovable property on own account.',
            ],
            [
                'title' => 'Refund of unutilized ITC on inverted duty structure',
                'section' => 'Section 54(3)',
                'court' => 'Supreme Court',
                'year' => 2024,
                'summary' => 'Refund permissible where input tax rate exceeds output tax rate.',
            ],
            [
                'title' => 'Transitional credit under Section 140',
                'section' => 'Section 140',
                'court' => 'Supreme Court',
                'year' => 2023,
                'summary' => 'Transitional credit is a vested right, time limit for claiming is directory.',
            ],
            [
                'title' => 'E-way bill penalty without intent to evade',
                'section' => 'Section 129',
                'court' => 'High Court',
                'year' => 2023,
                'summary' => 'Minor discrepancies in e-way bill should not attract penalty without evidence of tax evasion.',
            ],
            [
                'title' => 'IGST on ocean freight — reverse charge',
                'section' => 'Notification 10/2017',
                'court' => 'Supreme Court',
                'year' => 2022,
                'summary' => 'IGST on ocean freight under reverse charge held ultra vires.',
            ],
        ];

        $query = strtolower($query);
        return array_filter($allCases, function($case) use ($query) {
            return strpos(strtolower($case['title']), $query) !== false ||
                   strpos(strtolower($case['section']), $query) !== false ||
                   strpos(strtolower($case['summary']), $query) !== false;
        });
    }
}