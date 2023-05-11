<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\ArticelPrefrences;
use App\Services\ArticlesService;

class ArticlesController extends Controller
{
    public function index(Request $request)
    {
        $articles = [];
        if (auth()->check()) {
            $preferences = ArticelPrefrences::where(
                "user_id",
                auth()->user()->id
            )->first();
            $categories = json_decode($preferences->categories);
            $authors = json_decode($preferences->authors);
            $sources = json_decode($preferences->sources);

            $articles = ArticlesService::fetchArticlesFromAPIs(
                $categories,
                $authors,
                $sources
            );
        } else {
            $articles = ArticlesService::fetchArticlesFromAPIs();
        }

        return response()->json($articles);
    }

    public function getCategory()
    {
        // Fetch data from GuardianAPI
        $guardianApiResponse = ArticlesService::guardianApi();

        // Fetch data from New York Times API
        $nytApiResponse = ArticlesService::newsApiSource();

        $categoryNames = [];

        // Guardian API
        foreach ($guardianApiResponse["response"]["results"] as $result) {
            if ($result["sectionId"]) {
                $categoryNames[] = $result["sectionId"];
            }
        }

        // NYTimes API
        foreach ($nytApiResponse["sources"] as $doc) {
            if ($doc["category"]) {
                $categoryNames[] = $doc["category"];
            }
        }

        // Get only the unique categories
        $uniqueCategories = array_unique($categoryNames);

        // Create an array of objects with label and value properties
        $catrogries = [];
        foreach ($uniqueCategories as $catrogry) {
            $catrogries[] = [
                "label" => $catrogry,
                "value" => $catrogry, // Generate value from catrogry name (lowercase with no spaces)
            ];
        }

        return $catrogries;
    }

    public function getAuthor()
    {
        // Fetch data from NewsAPI
        $newsapiResponse = ArticlesService::newsApi();

        // Fetch data from GuardianAPI
        $guardianApiResponse = ArticlesService::guardianApi();

        // Fetch data from New York Times API
        $nytApiResponse = ArticlesService::nytimesApi();

        $authorNames = [];

        // NewsAPI
        foreach ($newsapiResponse["articles"] as $article) {
            if ($article["author"]) {
                $authorNames[] = $article["author"];
            }
        }

        // NYTimes API
        foreach ($nytApiResponse["response"]["docs"] as $doc) {
            if (
                $doc["byline"] &&
                isset($doc["byline"]["person"][0]["lastname"])
            ) {
                $authorNames[] = $doc["byline"]["person"][0]["lastname"];
            }
        }

        // Guardian API
        foreach ($guardianApiResponse["response"]["results"] as $result) {
            $authorNames[] =
                isset($result["fields"]["byline"]) ??
                $result["fields"]["byline"];
        }

        // Get only the unique authors
        $uniqueAuthors = array_unique($authorNames);

        // Create an array of objects with label and value properties
        $authors = [];
        foreach ($uniqueAuthors as $author) {
            $authors[] = [
                "label" => $author,
                "value" => $author, // Generate value from author name (lowercase with no spaces)
            ];
        }

        return $authors;
    }
}
