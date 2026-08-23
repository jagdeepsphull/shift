<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Puts the CSRF token into every page on its way out.
 *
 * Three things, all on the rendered HTML:
 *
 *  - a hidden input inside every `<form method="post">`,
 *  - a `<meta name="csrf-token">` in the head,
 *  - a one-line script that hands that meta value to jQuery's `ajaxSetup`, so
 *    the ajax posts (city lists, store defaults, shortlisting) send it as a
 *    header.
 *
 * Done here rather than by editing fifty views because the views are the part
 * of this application that changes most, and a form added next month with the
 * field forgotten is a 403 nobody sees until a user reports it. One place that
 * cannot be forgotten is worth the pass over the response body.
 *
 * A form that must not be given a token - none today - can opt out with
 * `data-no-csrf` on the tag.
 */
class CsrfTokenInjector implements FilterInterface
{
    /**
     * Nothing to do on the way in; CodeIgniter's own CSRF filter does the
     * checking.
     *
     * @param list<string>|null $arguments
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    /**
     * @param list<string>|null $arguments
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Only HTML. A CSV export, a JSON reply and an image are all responses
        // this must not touch.
        if (! str_contains(strtolower((string) $response->getHeaderLine('Content-Type')), 'text/html')) {
            return $response;
        }

        $body = (string) $response->getBody();

        if ($body === '') {
            return $response;
        }

        $name = csrf_token();

        $body = $this->injectIntoForms($body, $name);
        $body = $this->injectMeta($body);
        $body = $this->injectAjaxSetup($body);

        return $response->setBody($body);
    }

    /**
     * A hidden input straight after every opening `<form>` tag that posts.
     *
     * A tag with no `method` sends a GET and is left alone. So is one that
     * already carries the field, which is how a hand-written `csrf_field()` in
     * a view keeps working rather than being doubled.
     */
    private function injectIntoForms(string $body, string $name): string
    {
        $result = preg_replace_callback(
            '/<form\b[^>]*>/i',
            static function (array $m) use ($name): string {
                $tag = $m[0];

                if (! preg_match('/\bmethod\s*=\s*["\']?\s*post\b/i', $tag)) {
                    return $tag;
                }

                if (stripos($tag, 'data-no-csrf') !== false) {
                    return $tag;
                }

                // `csrf_hash()` masks the token differently on every call, so
                // each form on a page carries a different-looking value. They
                // all unmask to the one token the session holds.
                return $tag . '<input type="hidden" name="' . $name . '" value="' . csrf_hash() . '">';
            },
            $body
        );

        // A body large enough to blow the pcre backtrack limit comes back null.
        // Better to serve the page without the field - the form then fails a
        // CSRF check the user can retry - than to serve nothing at all.
        return $result ?? $body;
    }

    /** The token where script can read it, for the ajax calls. */
    private function injectMeta(string $body): string
    {
        if (stripos($body, 'name="csrf-token"') !== false) {
            return $body;
        }

        $meta = '<meta name="csrf-token" content="' . csrf_hash() . '">';

        $result = preg_replace('/<head\b[^>]*>/i', '$0' . $meta, $body, 1);

        return $result ?? $body;
    }

    /**
     * Hand the token to jQuery for every ajax call on the page.
     *
     * Last thing before `</body>`, so jQuery is loaded by the time it runs; the
     * ajax calls themselves all fire from event handlers later still. Pages
     * without jQuery, and ajax fragments with no body tag at all, are left
     * exactly as they are.
     */
    private function injectAjaxSetup(string $body): string
    {
        $pos = strripos($body, '</body>');

        if ($pos === false) {
            return $body;
        }

        $script = '<script>(function(){'
            . 'if(typeof jQuery==="undefined")return;'
            . 'var m=document.querySelector(\'meta[name="csrf-token"]\');'
            . 'if(!m)return;'
            . 'jQuery.ajaxSetup({headers:{"' . csrf_header() . '":m.getAttribute("content")}});'
            . '})();</script>';

        return substr($body, 0, $pos) . $script . substr($body, $pos);
    }
}
