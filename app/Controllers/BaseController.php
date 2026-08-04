<?php

namespace App\Controllers;

use App\Libraries\ConfigCompat;
use App\Libraries\FormValidationCompat;
use App\Libraries\InputCompat;
use App\Libraries\LoaderCompat;
use App\Libraries\SessionCompat;
use App\Libraries\UriCompat;
use App\Models\CustomModel;
use CodeIgniter\Controller;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Base controller for the PickAShift application.
 *
 * The application was migrated from CodeIgniter 3. To keep the controllers and
 * the helper functions readable against the original, this class exposes the
 * CI3 properties they used (`$this->custom`, `$this->db`, `$this->session`,
 * `$this->input`, `$this->uri`, `$this->config`, `$this->load`,
 * `$this->form_validation` and `$this->data`) backed by CodeIgniter 4 services.
 *
 * New code should prefer the native CI4 equivalents: `$this->request`,
 * `$this->response`, `model()`, `session()`, `service('validation')`.
 */
abstract class BaseController extends Controller
{
    /**
     * The currently executing controller, so that the ported helper functions
     * can reach it the way CI3's `get_instance()` did.
     */
    protected static ?BaseController $instance = null;

    /**
     * Helpers loaded for every controller. `ci3compat` comes first so its
     * CI3-flavoured `validation_errors()` / `set_value()` win over the CI4
     * form-helper versions (see Config\Autoload).
     */
    protected $helpers = ['ci3compat', 'url', 'form', 'text', 'common', 'captcha'];

    /** View data collected by the controller (CI3 `$this->data`). */
    public array $data = [];

    /** Generic data model (CI3 `$this->custom`). */
    public CustomModel $custom;

    /** Database connection (CI3 `$this->db`). */
    public BaseConnection $db;

    public SessionCompat $session;

    public InputCompat $input;

    public UriCompat $uri;

    public ConfigCompat $config;

    public LoaderCompat $load;

    public FormValidationCompat $form_validation;

    /** Name of the connected database (CI3 `$this->db->database`). */
    public string $dbname = '';

    /** Login state, set by the individual controllers. */
    public $isUserLoggedIn;

    public $isAdminUserLoggedIn;

    /** @var mixed Rows for the signed-in user. */
    public $userinfo;

    /** @var mixed */
    public $clink;

    /** @var mixed Result sets cached on the controller by the CI3 code. */
    public $joblists;

    /** @var mixed */
    public $jobslist;

    /** @var mixed */
    public $appliedjobs;

    /** @var mixed */
    public $appliedlist;

    /** @var mixed */
    public $applicationslist;

    /** @var mixed */
    public $job_owner;

    /** @var mixed */
    public $users;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        self::$instance = $this;

        $this->custom          = new CustomModel();
        $this->db              = db_connect();
        $this->session         = new SessionCompat();
        $this->input           = new InputCompat();
        $this->uri             = new UriCompat();
        $this->config          = new ConfigCompat();
        $this->load            = new LoaderCompat();
        $this->form_validation = new FormValidationCompat($this);

        $this->dbname = $this->db->getDatabase();

        // Every page had the site settings row available (CI3 autoloaded it in
        // each controller constructor); the individual controllers still set it
        // themselves so the behaviour is unchanged.
    }

    /**
     * The controller handling the current request (CI3 `get_instance()`).
     */
    public static function instance(): ?BaseController
    {
        return self::$instance;
    }

}
