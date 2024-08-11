<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class AnalysisController extends Controller
{

    public function analysis(string $id)
    {
        $sentimen = Storage::json('public/sentimen/sentimen.json');
        // Filter data berdasarkan id
        $filteredData = array_filter($sentimen, function ($item) use ($id) {
            return $item['Event Info']['id'] == $id;
        });
        // Konversi hasil filter ke array
        $filteredData = array_values($filteredData);
        return view('web-scraping.analysis', [
            'title' => 'Analysis',
            'sentiments' => $filteredData
        ]);
    }

    public function sentiment(Request $request)
    {
        // Validasi request
        $request->validate([
            'sentimen' => 'required|file|mimes:json',
        ]);

        try {
            // Cek dan hapus direktori jika sudah ada
            if (Storage::exists('public/sentimen')) {
                Storage::deleteDirectory('public/sentimen');
            }

            // Buat ulang direktori
            Storage::makeDirectory('public/sentimen');

            // Simpan file
            $file = $request->file('sentimen');
            $file->storeAs('public/sentimen', 'sentimen.json');
            return to_route('web-scrap.index')->with(['status' => 'success', 'msg' => "Uploaded"]);
        } catch (\Exception $e) {
            return back()->with(['status' => 'error', 'msg' => "Upload failed: " . $e->getMessage()]);
        }
    }

    public function solution(Request $request, $reason)
    {
        $reason = str_replace("-", " ", $reason);
        $cacheKey = 'solution_' . md5($reason); // Generate a unique cache key

        // Attempt to retrieve the cached response
        $cachedSolution = Cache::get($cacheKey);
        if ($cachedSolution) {
            // If the request is to download JSON, create the JSON file and return it
            if ($request->query('json') === 'download') {
                // Prepare the JSON file for download
                $filePath = 'exports/solution.json';
                $json = json_encode($cachedSolution, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                Storage::put($filePath, $json);

                return response()->download(storage_path('app/' . $filePath), 'solution.json')->deleteFileAfterSend(true);
            } else {
                // Return the cached data as JSON response
                return response()->json($cachedSolution);
            }
        }

        $apiKey = env('OPEN_API_KEY');
        $client = new Client();

        $systemContent = <<<EOT
        You are an AI designed to provide comprehensive solution recommendations in Bahasa Indonesia in a structured JSON format.
        Please generate a JSON output with the following structure:
        {
            "solution": {
                "issue": "This should match the issue provided in the input",
                "recommendations": [
                    {
                        "title": "Generated Recommendation Title",
                        "description": "Detailed explanation of the recommendation",
                        "legal_reference": "Relevant legal references",
                        "implementation_strategy": "Implementation strategy for the recommendation"
                    }
                ],
                "presentation": "How this solution can be implemented broadly by society and government"
            }
        }
    EOT;

        $userContent = <<<EOT
        Objective: Create a comprehensive solution and recommendation for the social issue: $reason.
        Context: The solution recommendation should specifically address the area of Poverty and Economic Inequality, Health and Well-being, Education and Literacy, Violence and Security, or Environment and Social Life in accordance with the prevailing laws in Indonesia.
        Intent: Improve the quality and welfare of social life and enhance the performance of the government as a policymaker.
        Instructions: Provide detailed, comprehensive recommendations with references to relevant regulations.
        Presentation: The solution should be feasible for implementation by society at large and specifically by the government.
    EOT;

        try {
            $response = $client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-4',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemContent],
                        ['role' => 'user', 'content' => $userContent],
                    ],
                    'max_tokens' => 1500,
                    'temperature' => 0.7,
                ],
            ]);

            $responseBody = json_decode($response->getBody()->getContents(), true);
            $solution = $responseBody['choices'][0]['message']['content'];
            $solution = trim($solution);

            $jsonObject = json_decode($solution, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                // Store the result in the cache for 12 hours (43200 seconds)
                Cache::put($cacheKey, $jsonObject, 43200);

                if ($request->query('json') === 'download') {
                    // Prepare the JSON file for download
                    $filePath = 'exports/solution.json';
                    $json = json_encode($jsonObject, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                    Storage::put($filePath, $json);

                    return response()->download(storage_path('app/' . $filePath), 'solution.json')->deleteFileAfterSend(true);
                } else {
                    // Return the data as a JSON response
                    return response()->json($jsonObject);
                }
            } else {
                throw new \Exception("Error decoding JSON: " . json_last_error_msg());
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
