<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Puts the form spinner on every page on its way out.
 *
 * Two things on the rendered HTML:
 *
 *  - a stylesheet in the head,
 *  - the script before `</body>`, where jQuery is already loaded - the script
 *    asks jQuery Validate about validated forms, and a copy that ran first
 *    would find nothing to ask.
 *
 * Done here rather than by editing seventy-two forms across four themes, for
 * the reason CsrfTokenInjector gives for the token: the views are the part of
 * this application that changes most, and a form added next month with the
 * spinner forgotten is a screen that looks frozen on a slow connection, which
 * is exactly the screen somebody presses twice.
 *
 * A form that must not have one opts out with `data-no-spinner` on the tag.
 *
 * @see \App\Filters\CsrfTokenInjector the same approach, for the CSRF field
 */
class FormSubmitSpinner implements FilterInterface
{
    /**
     * Nothing to do on the way in.
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

        // Already there. An ajax fragment rendered into a page that has it, or
        // a second pass, should not get a second copy.
        if (str_contains($body, 'form-spinner.js')) {
            return $response;
        }

        helper('url');

        $body = $this->injectStyles($body);
        $body = $this->injectScript($body);

        return $response->setBody($body);
    }

    /** The stylesheet, first thing in the head. */
    private function injectStyles(string $body): string
    {
        $link = '<link rel="stylesheet" href="' . $this->asset('assets/common/form-spinner.css') . '">';

        $result = preg_replace('/<head\b[^>]*>/i', '$0' . $link, $body, 1);

        return $result ?? $body;
    }

    /**
     * The script, last thing before `</body>`.
     *
     * `defer` is deliberately not used. The script binds its listeners the
     * moment it runs, and a deferred copy is not guaranteed to have done so
     * before somebody presses a button on a page that is otherwise ready -
     * which on a slow connection is precisely when this matters.
     *
     * A fragment with no body tag - an ajax partial - is left exactly as it is.
     */
    private function injectScript(string $body): string
    {
        $pos = strripos($body, '</body>');

        if ($pos === false) {
            return $body;
        }

        $script = '<script src="' . $this->asset('assets/common/form-spinner.js') . '"></script>';

        return substr($body, 0, $pos) . $script . substr($body, $pos);
    }

    /**
     * A URL for a shipped asset, stamped with the file's own timestamp.
     *
     * Deploys here are a hand-uploaded zip, so there is no build step to rename
     * anything and no cache to clear. Without the stamp a browser that has the
     * old copy keeps it, and the change appears not to have been uploaded at
     * all - which is the kind of thing that gets debugged on the server.
     */
    private function asset(string $path): string
    {
        $stamp = @filemtime(FCPATH . $path);

        $url = base_url($path);

        return $stamp === false ? $url : $url . '?v=' . $stamp;
    }
}
