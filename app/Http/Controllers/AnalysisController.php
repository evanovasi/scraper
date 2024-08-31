<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use App\Models\Analysis;
use App\Models\Scraping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AnalysisController extends Controller
{
    protected $apiKey;
    protected $client;

    public function __construct()
    {
        $this->apiKey = env('OPEN_API_KEY');
        $this->client = new Client();
    }

    public function index(Request $request, string $id)
    {
        // Mencari record berdasarkan ID
        $scraping = Scraping::findOrFail($id);

        // Mendapatkan bahasa dari query parameter, default ke 'English'
        $lang = $request->query('lang') == 'id' ? 'Bahasa Indonesia' : 'English';

        // Membuat key sentiment
        $sentimentKey = 'sentiment_' . $lang . md5($scraping->title);

        // Cek apakah analisis sudah ada atau buat baru jika tidak ada
        $analysis = Analysis::firstOrNew(['key' => $sentimentKey]);

        // Jika tidak ada, panggil fungsi _sentiment untuk mengenerate data sentiment
        if (empty($analysis->value)) {
            $sentiment = $this->_sentiment($lang, $id);
            if (empty($sentiment)) {
                return response()->json(['error' => 'No sentiment data found.'], 404);
            }
            $analysis->value = json_encode($sentiment);
        }

        // Simpan ke database setelah memastikan data sentiment ada
        $analysis->key = $sentimentKey;
        $analysis->save();

        // Mendapatkan data sentiment dari database
        $sentiment = json_decode($analysis->value, true);


        // Tampilkan data
        return view('web-scraping.analysis', [
            'title' => 'Analysis',
            'sentiment' => $sentiment,
        ]);
    }


    public function getSolution(Request $request, $reason)
    {
        $lang = $request->query('lang') == 'id' ? 'Bahasa Indonesia' :  'English';
        $loc = $request->query('loc');

        $reason = str_replace("-", " ", $reason);
        $solutionKey = 'solution_' . $lang . '_' . $loc . '_' . md5($reason);

        $analysis = Analysis::firstOrNew(['key' => $solutionKey]);

        // Jika tidak ada, panggil fungsi _solution untuk mengenerate data solution
        if (empty($analysis->value)) {
            $solution = $this->_solution($reason, $lang, $loc);
            if (empty($solution)) {
                return response()->json(['error' => 'No solution data found.'], 404);
            }
            $analysis->value = json_encode($solution);
        }

        // Simpan ke database setelah memastikan data solution ada
        $analysis->key = $solutionKey;
        $analysis->save();

        $solution = json_decode($analysis->value, true);

        return response()->json($solution);
    }



    private function _sentiment($lang,  $id)
    {
        $scraping = Scraping::findOrFail($id);
        // Struktur prompt yang digunakan
        $systemContent = "
        You are an AI designed to provide comprehensive sentiment Analyst translated in $lang and structured in a valid JSON format only.
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
                $json = json_decode($sentimen, true);

                // Memastikan hasil adalah JSON yang valid
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $json;
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

    private function _solution($reason, $lang, $loc)
    {
        $systemContent = <<<EOT
        You are an AI designed to provide comprehensive solution recommendations translated in $lang and structured in a valid JSON format only.
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
                $solution = $responseBody['choices'][0]['message']['content'];
                $json = json_decode($solution, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $json;
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
