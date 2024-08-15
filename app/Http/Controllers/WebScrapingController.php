<?php

namespace App\Http\Controllers;


use DateTime;
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
    public function index(Request $request)
    {
        $scrapings = Scraping::select('hashtags')->get();
        // Menggabungkan semua tags dari setiap record
        $allTags = [];
        foreach ($scrapings as $scraping) {
            $tags = explode(',', $scraping->hashtags);
            $allTags = array_merge($allTags, $tags);
        }

        // Menghilangkan duplikat dan menghilangkan spasi di setiap tag
        $allTags = array_unique(array_map('trim', $allTags));

        // Daftar kelas warna Bootstrap untuk badge
        $badgeColors = ['badge-primary', 'badge-secondary', 'badge-success', 'badge-danger', 'badge-warning', 'badge-info', 'badge-light', 'badge-dark'];

        // Menambahkan warna secara acak ke setiap tag
        $tagsWithColors = [];
        foreach ($allTags as $tag) {
            $randomColor = $badgeColors[array_rand($badgeColors)];
            $tagsWithColors[] = ['tag' => $tag, 'color' => $randomColor];
        }

        return view('web-scraping.index', [
            'title' => 'Web News Scraper',
            'datascrapings' => Scraping::latest()->filter($request)->where('type', 'web')->paginate(10)->withQueryString(),
            'tagsWithColors' => $tagsWithColors,
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
                $title =  $crawler->filter('h1')->first()->text();
                if ($primaryDomain == 'cnn.com') {
                    $dateString  = str_replace('Updated ', '', $crawler->filter('div.timestamp.vossi-timestamp-primary-core-light')->text());
                    $date = DateTime::createFromFormat('g:i A T, D F d, Y', $dateString)->format('Y-m-d');
                    $contents =  $crawler->filter('div.article__content p')->each(function ($node) {
                        return $node->text();
                    });
                    $content = implode(' ', array_filter($contents));
                    $tags = [];
                } elseif ($primaryDomain == 'bbc.com') {
                    $scriptContent = $crawler->filter('script[type="application/ld+json"]')->text();
                    $data = json_decode($scriptContent, true);
                    if (isset($data['datePublished'])) {
                        $datePublished = new DateTime($data['datePublished']);
                        $date = $datePublished->format('Y-m-d');
                    }
                    $contents =  $crawler->filter('div.sc-18fde0d6-0.dlWCEZ p')->each(function ($node) {
                        return $node->text();
                    });
                    $content = implode(' ', array_filter($contents));
                    $tags = [];
                } elseif ($primaryDomain == 'detik.com') {
                    $date = $crawler->filterXPath('//meta[@name="dtk:publishdate"]')->attr('content');
                    $content = $crawler->filter('div.detail__body')->first()->text();
                    $tags = $crawler->filter('a[dtr-act="tag"]')->each(function ($node) {
                        return $node->text();
                    });
                } else if ($primaryDomain == 'kompas.com') {
                    $date = $crawler->filterXPath('//meta[@name="content_PublishedDate"]')->attr('content');
                    $contents = $crawler->filter('div.read__content')->filter('div.clearfix p')->each(function ($node) {
                        return $node->text();
                    });
                    $content = implode(' ', array_filter($contents));
                    $tags = $crawler->filter('li.tag__article__item')->each(function ($node) {
                        return $node->text();
                    });
                } else if ($primaryDomain == 'liputan6.com') {
                    $date = $crawler->filterXPath('//meta[@property="article:published_time"]')->attr('content');
                    $contents = $crawler->filter('div.article-content-body__item-content p')->each(function ($node) {
                        return $node->text();
                    });
                    $content = implode(' ', array_filter($contents));
                    $tags = $crawler->filter('li.tags--snippet__item')->each(function ($node) {
                        return $node->text();
                    });
                } else if ($primaryDomain == 'antaranews.com') {
                    $date = $crawler->filterXPath('//meta[@property="article:published_time"]')->attr('content');
                    $content = $crawler->filter('div.wrap__article-detail-content')->first()->text();
                    $tags = $crawler->filter('div.blog-tags ul.list-inline li.list-inline-item')->each(function ($node) {
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
                $scraping->hashtags = implode(', ', array_filter($tags));
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
