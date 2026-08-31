<?php

use App\Filters\FormSubmitSpinner;
use CodeIgniter\HTTP\Response;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App;

/**
 * The spinner that appears in a submit button while a form is on its way.
 *
 * Most of what is worth checking here is not "does it inject" - that is one
 * regex - but the two things about this application that make the browser side
 * delicate, both of which fail silently and neither of which any screen shows:
 *
 *  - a disabled control is not submitted, and fifty-six submit buttons here
 *    carry a name that thirty-two controller branches switch on;
 *  - jQuery Validate stops the submit event from ever reaching the document,
 *    so the listener has to be in the capture phase.
 *
 * Both are one well-meaning simplification away from being undone, so the
 * asserts below name them.
 *
 * @internal
 */
final class FormSubmitSpinnerTest extends CIUnitTestCase
{
    private function respond(string $body, string $type = 'text/html'): Response
    {
        $response = new Response(new App());
        $response->setHeader('Content-Type', $type);
        $response->setBody($body);

        return $response;
    }

    private function filtered(string $body, string $type = 'text/html'): string
    {
        $filter = new FormSubmitSpinner();

        $out = $filter->after(service('request'), $this->respond($body, $type));

        return (string) $out->getBody();
    }

    // ------------------------------------------------------------ injection --

    public function testPutsBothAssetsIntoAnHtmlPage(): void
    {
        $html = $this->filtered('<html><head><title>x</title></head><body><form method="post"></form></body></html>');

        $this->assertStringContainsString('form-spinner.css', $html, 'the stylesheet is not in the head');
        $this->assertStringContainsString('form-spinner.js', $html, 'the script is not before </body>');
    }

    public function testScriptGoesLastSoJqueryValidateIsAlreadyThereToBeAsked(): void
    {
        $html = $this->filtered('<html><head></head><body><script src="jquery.js"></script></body></html>');

        $this->assertLessThan(
            strpos($html, 'form-spinner.js'),
            strpos($html, 'jquery.js'),
            'the spinner script must come after jQuery - it asks jQuery Validate about validated forms',
        );
    }

    public function testLeavesRepliesThatAreNotHtmlAlone(): void
    {
        // The monthly report is streamed as a CSV and the ajax city lookups
        // answer JSON. A script tag in either is corruption, not a spinner.
        foreach (['application/json', 'text/csv', 'image/png'] as $type) {
            $body = $this->filtered('{"cities":[]}', $type);

            $this->assertStringNotContainsString('form-spinner', $body, "{$type} was modified");
        }
    }

    public function testDoesNotInjectTwice(): void
    {
        $once  = $this->filtered('<html><head></head><body></body></html>');
        $twice = $this->filtered($once);

        $this->assertSame(1, substr_count($twice, 'form-spinner.js'));
        $this->assertSame(1, substr_count($twice, 'form-spinner.css'));
    }

    public function testAjaxFragmentWithNoBodyTagGetsNoScript(): void
    {
        // Several admin screens render a bare <option> list into a select.
        $html = $this->filtered('<option value="1">Toronto</option>');

        $this->assertStringNotContainsString('<script', $html);
    }

    public function testEmptyBodyIsUntouched(): void
    {
        $this->assertSame('', $this->filtered(''));
    }

    // ---------------------------------------------------------- the assets --

    public function testTheAssetsItPointsAtAreActuallyShipped(): void
    {
        // The filter names two files by path. A rename that misses one is a
        // 404 and no spinner anywhere, with nothing in any log to say so.
        foreach (['assets/common/form-spinner.css', 'assets/common/form-spinner.js'] as $path) {
            $this->assertFileExists(ROOTPATH . $path, "{$path} is named by the filter but not in the tree");
        }
    }

    /**
     * The submit button's name has to survive being disabled.
     *
     * `Sadmin::post('savedata')` and its thirty-one siblings do nothing without
     * it, so a version of this script that simply disables the button turns
     * every save in the back office into a page that reloads and saves nothing.
     */
    public function testScriptCarriesTheSubmitButtonsNameIntoTheForm(): void
    {
        $js = (string) file_get_contents(ROOTPATH . 'assets/common/form-spinner.js');

        $this->assertStringContainsString("keep.type = 'hidden'", $js);
        $this->assertStringContainsString('keep.name = btn.name', $js);
        $this->assertStringContainsString('keep.value', $js);

        // Defined is not enough - it was defined and never called once already,
        // and nothing failed, because the disable happens a tick late and the
        // browser had already gathered the field. That is the accident this
        // exists to stop relying on, so check it is actually wired in.
        $this->assertMatchesRegularExpression(
            '/^\s*carryOver\(form, btn\);/m',
            $js,
            'carryOver() is defined but never called - the safety net is not attached',
        );
    }

    /**
     * The submit listener has to be in the capture phase.
     *
     * jQuery Validate answers `return false`, which in jQuery is preventDefault
     * *and* stopPropagation. A listener in the bubble phase never runs for a
     * validated form - which is sign-in, registration and both password screens.
     */
    public function testScriptListensInTheCapturePhaseAndAsksTheValidator(): void
    {
        $js = (string) file_get_contents(ROOTPATH . 'assets/common/form-spinner.js');

        $this->assertMatchesRegularExpression(
            '/addEventListener\(\s*[\'"]submit[\'"].*?,\s*true\s*\)/s',
            $js,
            'the submit listener is no longer registered in the capture phase',
        );

        $this->assertStringContainsString('checkForm()', $js, 'it no longer asks jQuery Validate');
    }

    /**
     * The ring drawn over an <input type="submit"> needs a z-index.
     *
     * Both themes put `position: relative; z-index: 1` on `.btn`, which paints
     * the button above anything at z-index auto - so the ring ends up behind
     * the button and is simply not on the screen. It is a nasty one to chase
     * because nothing measures as wrong: the element is there, visible, the
     * right colour, and on top by `elementFromPoint` (hit-testing skips the
     * disabled input, so it wins the hit test it loses the paint).
     */
    public function testTheRingOverAnInputIsStackedAboveTheButton(): void
    {
        $css = (string) file_get_contents(ROOTPATH . 'assets/common/form-spinner.css');

        $this->assertMatchesRegularExpression(
            '/\.pas-spin--over\s*\{[^}]*z-index\s*:\s*[1-9]/s',
            $css,
            'the overlaid ring has no z-index - it will paint behind the button and be invisible',
        );
    }

    public function testScriptHonoursTheOptOutAndGuardsAgainstDoubleSubmit(): void
    {
        $js = (string) file_get_contents(ROOTPATH . 'assets/common/form-spinner.js');

        $this->assertStringContainsString('data-no-spinner', $js);
        $this->assertStringContainsString('preventDefault', $js);
    }
}
