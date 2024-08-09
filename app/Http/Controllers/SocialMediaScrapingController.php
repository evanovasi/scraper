<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use App\Models\Scraping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\DomCrawler\Crawler;

class SocialMediaScrapingController extends Controller
{
    public function index()
    {
        return view('socmed-scraping.index', [
            'title' => 'Social Media Scraper',
            'datascrapings' => Scraping::latest()->where('type', 'socmed')->paginate(10)->withQueryString()
        ]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'hashtags' => 'required', // Validasi setiap elemen dalam array urls agar merupakan URL yang valid
        ]);

        if ($request->platform == 'ig') {
            // $url = 'https://www.instagram.com/explore/tags/' . $request->hashtags . '/';
            $url = 'https://www.instagram.com/api/graphql/';
            $client = new Client();
            $response = $client->request('GET', $url);
            $html = $response->getBody()->getContents();

            $crawler = new Crawler($html);
            // $scriptNodes = $crawler->filterXPath('//script[contains(text(), "window._sharedData")]');
            // $script = $scriptNodes->first()->text();
            // $script = substr($script, strpos($script, '{'));
            // $script = substr($script, 0, strrpos($script, ';'));
            // $data = json_decode($script, true);


            dd($crawler);
        }
        // if (!$saveScrap['status']) {
        //     return to_route('socmed-scrap.index')->with(['status' => 'danger', 'msg' => "Failed : " . $saveScrap['error']]);
        // }
        // return to_route('socmed-scrap.index')->with(['status' => 'success', 'msg' => "Success"]);
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

                if ($primaryDomain == 'instagram.com') {
                    // $date = $crawler->filterXPath('//meta[@name="dtk:publishdate"]')->attr('content');
                    $content = $crawler->filter('div.x193iq5w.xeuugli.x1fj9vlw.x13faqbe.x1vvkbs.xt0psk2.x1i0vuye.xvs91rp.xo1l8bm.x5n08af.x10wh9bi.x1wdrske.x8viiok.x18hxmgj')->first()->text();
                    // $hashtags = $crawler->filter('a[dtr-act="tag"]')->each(function ($node) {
                    //     return $node->text();
                    // });
                } else {
                    $date = 'url situs ini belum ditambahkan!';
                    $content = 'url situs ini belum ditambahkan!';
                }
                //  save
                // $scraping = new Scraping();
                // $scraping->type = 'web';
                // $scraping->date = date('Y-m-d', strtotime($date));
                // $scraping->title = $title;
                // $scraping->content = $content;
                // $scraping->url = $url;
                // $scraping->hashtags = implode(', ', array_filter($hashtags));
                // $scraping->save();

                $results = [
                    'status' => true,
                    'data' => $content,
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
}
