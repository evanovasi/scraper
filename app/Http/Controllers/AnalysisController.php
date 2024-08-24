<?php

namespace App\Http\Controllers;

use App\Models\Scraping;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class AnalysisController extends Controller
{

    protected $apiKey;
    protected $client;
    public function __construct()
    {
        $this->apiKey = env('OPEN_API_KEY');
        $this->client = new Client();
    }

    public function analysis(Request $request, string $id)
    {
        $lang = $request->query('lang') == 'id' ? 'Bahasa Indonesia' :  'English';
        // $lang = $request->query('lang') == 'id' ? 'Bahasa Indonesia' : ($request->query('lang') == 'en' ? 'English' : false);

        $scraping = Scraping::findOrFail($id);
        $cacheKey = 'sentiment_' . $lang . md5($scraping->title);

        // Attempt to retrieve the cached response
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return view('web-scraping.analysis', [
                'title' => 'Analysis',
                'sentiments' => $cached
            ]);
        }

        // Struktur prompt yang digunakan
        $systemContent = "
            You are an AI designed to provide comprehensive sentiment Analyst translated in $lang and structured JSON formats.
            Please generate a JSON output with the following structure:
            {
                'Event Info': {
                    'id': '{$scraping->id}',
                    'date': '{$scraping->date}',
                    'title': 'Generated Recommendation Title from title/content',
                    'cluster': 'Cluster : [Poverty and Economic Inequality, Health and Welfare, Education and Literacy, Violence and Security, Environment and Social Life, Others]',
                    'speaker': 'identify the figure who made the statement',
                    'location': 'Identify the country where the news occurred.'
                },
                'Aspect Sentiments': [
                    {
                    'subject': 'The output is in the form of names of figures or governments or communities',
                    'reason': 'The output is in the form of a statement or response delivered by the subject in response to the context that occurred',
                    'sentiment': 'The output is in the form of basic sentiment analysis: Positive, Neutral, Negative',
                    'tone': 'The output in the form of sentiment analysis in the context of communication is as follows: Support, Suggestion, Criticism, Complaints or Other',
                    'object': 'The output is a classification of whether the statement is included in: Individual, Organization, Policy or Other'
                    }
                ],
                'Executive Summary': 'A concise paraphrase of the topic Content',
                'Topics': [
                    'output in the form of keywords as hashtags from the news in the Content variable'
                ]
            }
            ";

        $userContent = <<<EOT
            Objective: Create a comprehensive sentiment analysis of the provided {$scraping->title}.
            Context: Data context is taken from the variable {$scraping->content}. The sentiment analysis context specifically focuses on the responses expressed on a topic within news content in Indonesia.
            Intent: To gather data on a person's response or sentiment towards a situation or government policy. This is aimed at improving the quality and welfare of social life and enhancing the performance of the government as policy makers.
            Instructions: Identify the predetermined sentiment aspects. These include Sentiment: [Positive, Neutral, Negative], Tone: [Support, Suggestion, Criticism, Complaint, Others], Object: [Individual, Organization, Policy, Others], Cluster: [Poverty and Economic Inequality, Health and Welfare, Education and Literacy, Violence and Security, Environment and Social Life, Others].
        EOT;


        try {
            $response = $this->client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-4-turbo',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemContent],
                        ['role' => 'user', 'content' => $userContent],
                    ],
                    'max_tokens' => 1500,
                    'temperature' => 0.7,
                ],
            ]);

            $responseBody = json_decode($response->getBody()->getContents(), true);

            if (isset($responseBody['choices'][0]['message']['content'])) {
                $sentimen = $responseBody['choices'][0]['message']['content'];
                // Ubah JSON menjadi array
                $json = json_decode($sentimen, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    Cache::put($cacheKey, $json, 43200);
                    return view('web-scraping.analysis', [
                        'title' => 'Analysis',
                        'sentiments' => $json
                    ]);
                } else {
                    throw new \Exception("Error decoding JSON: " . json_last_error_msg());
                }
            } else {
                throw new \Exception("Invalid response structure from OpenAI API.");
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function solution(Request $request, $reason)
    {
        $lang = $request->query('lang') == 'id' ? 'Bahasa Indonesia' :  'English';
        $loc = $request->query('loc');

        $reason = str_replace("-", " ", $reason);
        $cacheKey = 'solution_' . $lang . '_' . $loc . '_' . md5($reason);

        // Attempt to retrieve the cached response
        $cached = Cache::get($cacheKey);
        if ($cached) {
            // If the request is to download JSON, create the JSON file and return it
            if ($request->query('json') === 'download') {
                // Prepare the JSON file for download
                $filePath = 'exports/solution.json';
                $json = json_encode($cached, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                Storage::put($filePath, $json);

                return response()->download(storage_path('app/' . $filePath), 'solution.json')->deleteFileAfterSend(true);
            } else {
                // Return the cached data as JSON response
                return response()->json($cached);
            }
        }

        $systemContent = <<<EOT
        You are an AI designed to provide comprehensive solution recommendations translated in $lang and structured JSON formats.
        Please generate a JSON output with the following structure:
        {
            "solution": {
                "issue": "This should match the issue provided in the input",
                "recommendations": [
                    {
                        "title": "Generated Recommendation Title",
                        "description": "Detailed explanation of the recommendation",
                        "legal_reference": "Some comprehensive and contextually relevant up-to-date legal references in $loc",
                        "implementation_strategy": "strategy implementation recommendations with detailed steps in concrete points of implementation from start to finish, mention the parties or stakeholders who are interrelated and need to work together in order to succeed. Provide real implementation examples for each of the concrete points."
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
        Instructions: Provide detailed and comprehensive recommendations with reference to relevant regulations in $loc. Provide several references to related and current laws and regulations.
        Presentation: The solution should be feasible for implementation by society at large and specifically by the government.
    EOT;

        try {
            $response = $this->client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-4-turbo',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemContent],
                        ['role' => 'user', 'content' => $userContent],
                    ],
                    'max_tokens' => 1500,
                    'temperature' => 0.7,
                ],
            ]);

            $responseBody = json_decode($response->getBody()->getContents(), true);

            if (isset($responseBody['choices'][0]['message']['content'])) {
                $solution = trim($responseBody['choices'][0]['message']['content']);
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
            } else {
                throw new \Exception("Invalid response structure from OpenAI API.");
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
