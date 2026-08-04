<?php

namespace App\Libraries;

use CodeIgniter\Validation\ValidationInterface;

/**
 * A thin CodeIgniter 3 `form_validation` facade over CodeIgniter 4 validation.
 *
 * Only the surface the PickAShift application actually used is implemented:
 * `set_rules()`, `set_message()`, `run()` and `error_string()`. Rule strings are
 * passed through unchanged (`required|valid_email|is_unique[users.u_userid]`),
 * except for CI3 `callback_foo` rules, which are turned into a closure that
 * calls `foo()` on the controller - exactly what CI3 did.
 */
class FormValidationCompat
{
    protected ValidationInterface $validation;

    /** The controller that owns this instance (for `callback_*` rules). */
    protected object $controller;

    /** @var array<string, array{label: string, rules: array}> */
    protected array $fields = [];

    /** @var array<string, string> rule => message override */
    protected array $messages = [];

    public function __construct(object $controller)
    {
        $this->controller = $controller;
        $this->validation = service('validation');
        $this->validation->reset();
    }

    /**
     * CI3: `$this->form_validation->set_rules($field, $label, $rules)`.
     *
     * @param array|string $rules
     */
    public function set_rules(string $field, string $label = '', $rules = [], array $errors = []): self
    {
        if (is_string($rules)) {
            $rules = $rules === '' ? [] : explode('|', $rules);
        }

        $prepared = [];

        foreach ($rules as $rule) {
            if (is_string($rule) && str_starts_with($rule, 'callback_')) {
                $method = substr($rule, 9);
                $prepared[] = $this->makeCallbackRule($method);

                continue;
            }

            $prepared[] = $rule;
        }

        $this->fields[$field] = [
            'label'  => $label !== '' ? $label : $field,
            'rules'  => $prepared,
            'errors' => $errors,
        ];

        return $this;
    }

    /**
     * CI3: `$this->form_validation->set_message($rule, $message)`.
     *
     * CI3 used `%s` for the field name; CI4 uses `{field}`.
     */
    public function set_message(string $rule, string $message): self
    {
        $this->messages[$rule] = str_replace('%s', '{field}', $message);

        return $this;
    }

    /**
     * Run the rules against the POST data. Mirrors CI3: with no rules set at
     * all, validation is considered failed.
     */
    public function run(?array $data = null): bool
    {
        if ($this->fields === []) {
            return false;
        }

        $this->validation->reset();

        foreach ($this->fields as $field => $definition) {
            $errors = $definition['errors'];

            foreach ($this->messages as $rule => $message) {
                // A per-rule message only applies to the rules the field uses.
                if ($this->usesRule($definition['rules'], $rule) && ! isset($errors[$rule])) {
                    $errors[$rule] = $message;
                }
            }

            $this->validation->setRule($field, $definition['label'], $definition['rules'], $errors);
        }

        $data ??= service('request')->getPost() ?? [];

        return $this->validation->run($data);
    }

    /**
     * CI3: `$this->form_validation->error_string()` / `validation_errors()`.
     */
    public function error_string(string $prefix = '<p>', string $suffix = '</p>'): string
    {
        $out = '';

        foreach ($this->validation->getErrors() as $error) {
            $out .= $prefix . $error . $suffix . "\n";
        }

        return $out;
    }

    /**
     * CI3: `$this->form_validation->error($field)`.
     */
    public function error(string $field, string $prefix = '', string $suffix = ''): string
    {
        $error = $this->validation->getError($field);

        return $error === '' ? '' : $prefix . $error . $suffix;
    }

    /**
     * @return array<string, string>
     */
    public function error_array(): array
    {
        return $this->validation->getErrors();
    }

    /**
     * Forget every rule (CI3 had no public equivalent, handy between requests).
     */
    public function reset(): self
    {
        $this->fields   = [];
        $this->messages = [];
        $this->validation->reset();

        return $this;
    }

    /**
     * Wrap a controller method as a CI4 closure rule.
     */
    protected function makeCallbackRule(string $method): callable
    {
        $controller  = $this->controller;
        $ruleName    = 'callback_' . $method;
        $messages    = &$this->messages;
        $messageKey  = $method;

        return static function ($value, $data, ?string &$error) use ($controller, $method, $ruleName, &$messages, $messageKey): bool {
            if (! method_exists($controller, $method)) {
                return true;
            }

            $result = $controller->{$method}($value);

            if ($result === false) {
                $error = $messages[$messageKey] ?? $messages[$ruleName] ?? 'The {field} field is invalid.';

                return false;
            }

            return true;
        };
    }

    /**
     * Does this rule list contain the named rule (with or without parameters)?
     */
    protected function usesRule(array $rules, string $name): bool
    {
        foreach ($rules as $rule) {
            if (! is_string($rule)) {
                continue;
            }

            if ($rule === $name || str_starts_with($rule, $name . '[')) {
                return true;
            }
        }

        return false;
    }
}
