<?php

namespace App\Http\Controllers;


use GuzzleHttp\Client;
use App\Models\Scraping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Pagination\LengthAwarePaginator;

class WebScrapingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        return view('web-scraping.index', [
            'title' => 'Web News Scraper',
            'datascrapings' => Scraping::latest()->where('type', 'web')->paginate(10)->withQueryString()
        ]);
    }



    public function store(Request $request)
    {
        $request->validate([
            'urls.*' => 'required|url', // Validasi setiap elemen dalam array urls agar merupakan URL yang valid
        ], [
            'urls.*.required' => 'URL is required',
            'urls.*.url' => 'Please enter a valid URL',
        ]);

        $saveScrap = $this->extractHtml($request->urls);
        if (!$saveScrap['status']) {
            return to_route('web-scrap.index')->with(['status' => 'danger', 'msg' => "Failed : " . $saveScrap['error']]);
        }
        return to_route('web-scrap.index')->with(['status' => 'success', 'msg' => "Success"]);
    }

    public function toJSON(?int $id = null)
    {
        if (!empty($id)) {
            $scraping = Scraping::findOrFail($id);
        } else {
            $scraping = Scraping::all();
        }
        $json = $scraping->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $filePath = 'exports/datascraping.json';
        Storage::put($filePath, $json);

        if ($scraping->count() <= 0) {
            return to_route('web-scrap.index')->with(['status' => 'danger', 'msg' => "Data no found"]);
        }
        return response()->download(storage_path('app/' . $filePath))->deleteFileAfterSend(true);
    }

    private function extractHtml($urls)
    {
        $client = new Client();
        $results = [];
        foreach ($urls as $url) {
            try {
                $primaryDomain = $this->getPrimaryDomain($url);

                // Ambil halaman
                $response = $client->request('GET', $url);
                $html = $response->getBody()->getContents();

                // Parse HTML dengan DomCrawler
                $crawler = new Crawler($html);

                // Ekstrak judul berita
                $title = $crawler->filter('h1')->first()->text();

                if ($primaryDomain == 'detik.com') {
                    $date = $crawler->filterXPath('//meta[@name="dtk:publishdate"]')->attr('content');
                    $content = $crawler->filter('div.detail__body')->first()->text();
                    $hashtags = $crawler->filter('a[dtr-act="tag"]')->each(function ($node) {
                        return $node->text();
                    });
                } else if ($primaryDomain == 'kompas.com') {
                    $date = $crawler->filterXPath('//meta[@name="content_PublishedDate"]')->attr('content');
                    $contents = $crawler->filter('div.read__content')->filter('div.clearfix p')->each(function ($node) {
                        return $node->text();
                    });
                    $content = implode(' ', array_filter($contents));
                    $hashtags = $crawler->filter('li.tag__article__item')->each(function ($node) {
                        return $node->text();
                    });
                } else if ($primaryDomain == 'liputan6.com') {
                    $date = $crawler->filterXPath('//meta[@property="article:published_time"]')->attr('content');
                    $contents = $crawler->filter('div.article-content-body__item-content p')->each(function ($node) {
                        return $node->text();
                    });
                    $content = implode(' ', array_filter($contents));
                    $hashtags = $crawler->filter('li.tags--snippet__item')->each(function ($node) {
                        return $node->text();
                    });
                } else if ($primaryDomain == 'antaranews.com') {
                    $date = $crawler->filterXPath('//meta[@property="article:published_time"]')->attr('content');
                    $content = $crawler->filter('div.wrap__article-detail-content')->first()->text();
                    $hashtags = $crawler->filter('div.blog-tags ul.list-inline li.list-inline-item')->each(function ($node) {
                        return $node->text();
                    });
                } else {
                    $results = [
                        'status' => false,
                        'error' => 'Scrap data from this site is not yet available!', // Simpan pesan error jika terjadi
                    ];
                    continue;
                }
                //  save
                $scraping = new Scraping();
                $scraping->type = 'web';
                $scraping->date = date('Y-m-d', strtotime($date));
                $scraping->title = $title;
                $scraping->content = $content;
                $scraping->url = $url;
                $scraping->hashtags = implode(', ', array_filter($hashtags));
                $scraping->save();

                $results = [
                    'status' => true,
                    'data' => $scraping,
                ];
            } catch (\Exception $e) {
                // Tangani error jika ada
                $results = [
                    'status' => false,
                    'url' => $url,
                    'error' => $e->getMessage(), // Simpan pesan error jika terjadi
                ];
            }
        }
        return $results;
    }

    private function getPrimaryDomain($url)
    {
        // Menghapus http:// atau https:// dari URL
        $url = preg_replace("(^https?://)", "", $url);

        // Memisahkan URL berdasarkan tanda '/'
        $urlParts = explode('/', $url);

        // Mengambil domain utama
        $primaryDomain = $urlParts[0];

        // Menghilangkan subdomain
        $primaryDomainParts = explode('.', $primaryDomain);
        if (count($primaryDomainParts) > 2) {
            $primaryDomain = $primaryDomainParts[count($primaryDomainParts) - 2] . '.' . $primaryDomainParts[count($primaryDomainParts) - 1];
        }

        return $primaryDomain;
    }
    public function show(string $id)
    {
        return view('web-scraping.show', [
            'title' => 'Detail',
            'datascraping' => Scraping::findOrFail($id),
        ]);
    }

    public function analysis(string $id)
    {
        $sentimen = Storage::json('public/sentimen/sentimen.json');
        // Filter data berdasarkan id
        $filteredData = array_filter($sentimen, function ($item) use ($id) {
            return $item['Event Info']['id'] == $id;
        });
        // Konversi hasil filter ke array
        $filteredData = array_values($filteredData);
        // dd($filteredData);
        // // Tentukan jumlah item per halaman
        // $perPage = 10;
        // // Ambil halaman saat ini dari request
        // $currentPage = LengthAwarePaginator::resolveCurrentPage();
        // // Slice data untuk halaman saat ini
        // $currentItems = array_slice($filteredData, ($currentPage - 1) * $perPage, $perPage);
        // // Buat paginator
        // $paginator = new LengthAwarePaginator($currentItems, count($filteredData), $perPage, $currentPage, [
        //     'path' => LengthAwarePaginator::resolveCurrentPath(),
        //     'pageName' => 'page',
        // ]);
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $scraping = Scraping::findOrFail($id);
        $scraping->delete();
        return to_route('web-scrap.index')->with(['status' => 'success', 'msg' => "Deleted"]);
    }
}
