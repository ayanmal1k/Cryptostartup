<?php

namespace App\Http\Controllers;

use App\Providers\MembersHelperServiceProvider;
use Illuminate\Http\Request;

/**
 * Member lookup only. The /search page (posts, photos, videos, people and live filters)
 * was removed at the client's request; this JSON endpoint stays because the challenge
 * form's @username field, the home search and the mobile search overlay call it.
 */
class SearchController extends Controller
{
    /**
     * Paginated member search as JSON.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUsersSearch(Request $request)
    {
        $searchTerm = $request->get('query') ?: false;
        // Don't encode to HTML for API calls (like custom request form)
        $encodeHtml = $request->get('encode_html', false);

        return response()->json(['success' => true, 'data' => MembersHelperServiceProvider::getSearchUsers([
            'encodePostsToHtml' => $encodeHtml,
            'searchTerm' => $searchTerm,
            'gender' => $request->get('gender'),
            'min_age' => $request->get('min_age'),
            'max_age' => $request->get('max_age'),
            'location' => $request->get('location'),
        ])]);
    }
}
