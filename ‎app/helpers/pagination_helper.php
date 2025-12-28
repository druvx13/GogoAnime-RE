<?php

class PaginationHelper {

    /**
     * Renders the pagination HTML (<li> elements only).
     *
     * @param int $currentPage The current page number.
     * @param int $totalPages The total number of pages.
     * @param array $queryParams Existing query parameters (key => value) to preserve.
     * @param string $pageParam The name of the page parameter in the URL (default 'page').
     * @return string The generated HTML.
     */
    public static function render($currentPage, $totalPages, $queryParams = [], $pageParam = 'page') {
        if ($totalPages <= 1) {
            return '';
        }

        $currentPage = (int)$currentPage;
        if ($currentPage < 1) $currentPage = 1;
        if ($currentPage > $totalPages) $currentPage = $totalPages;

        // Logic for sliding window (max 3 pages)
        // If totalPages <= 3, show all
        // Else, center on currentPage

        $start = 1;
        $end = $totalPages;

        if ($totalPages > 3) {
            $start = max(1, $currentPage - 1);
            $end = min($totalPages, $start + 2);

            // Ensure we have exactly 3 items if possible
            if (($end - $start) < 2) {
                if ($start == 1) {
                    $end = 3;
                } elseif ($end == $totalPages) {
                    $start = $totalPages - 2;
                }
            }
        }

        $html = '';

        // Previous Link
        $prevPage = $currentPage - 1;

        if ($currentPage > 1) {
            $url = self::buildUrl($prevPage, $queryParams, $pageParam);
            $html .= "<li><a href='$url'>&lt;</a></li>"; // Using < for Previous
        } else {
             $html .= "<li class='disabled'><a>&lt;</a></li>";
        }

        for ($i = $start; $i <= $end; $i++) {
            $active = ($i == $currentPage) ? 'selected' : '';
            $url = self::buildUrl($i, $queryParams, $pageParam);
            $html .= "<li class='$active'><a href='$url'>$i</a></li>";
        }

        // Next Link
        $nextPage = $currentPage + 1;
        if ($currentPage < $totalPages) {
            $url = self::buildUrl($nextPage, $queryParams, $pageParam);
            $html .= "<li><a href='$url'>&gt;</a></li>"; // Using > for Next
        } else {
            $html .= "<li class='disabled'><a>&gt;</a></li>";
        }

        return $html;
    }

    private static function buildUrl($page, $queryParams, $pageParam) {
        $params = $queryParams;
        $params[$pageParam] = $page;
        return '?' . http_build_query($params);
    }
}
