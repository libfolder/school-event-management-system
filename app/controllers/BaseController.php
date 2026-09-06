<?php

namespace Controllers;

abstract class BaseController
{
    protected $f3;

    public function __construct()
    {
        $this->f3 = \Base::instance();
        $f3 = $this->f3;

        // Auto-load models into the registry
        $f3->set('models', [
            'User'      => new \App\Models\User(),
            'Class'     => new \App\Models\SchoolClass(),
            'Student'   => new \App\Models\Student(),
            'EventType' => new \App\Models\EventType(),
            'Event'     => new \App\Models\Event(),
            'Action'    => new \App\Models\Action(),
        ]);
    }

    protected function render(string $view, array $data = [], ?string $layout = null): void
    {
        // Always provide errors/old so templates can safely reference them
        $data += ['errors' => [], 'old' => []];

        foreach ($data as $key => $value) {
            $this->f3->set($key, $value);
        }

        if ($layout !== null) {
            $this->f3->set('content', \Template::instance()->render($view));
            echo \Template::instance()->render($layout);
            return;
        }

        echo \Template::instance()->render($view);
    }

    protected function redirect(string $path): void
    {
        $this->f3->reroute($path);
    }

    /* =========================================================
       VALIDATION
       ========================================================= */

    protected array $errors = [];

    protected function setError(string $field, string $message): void
    {
        $this->errors[$field] = $message;
    }

    protected function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    protected function errorFor(string $field): ?string
    {
        return $this->errors[$field] ?? null;
    }

    protected function postClean(string $key): string
    {
        $value = $this->f3->get('POST.' . $key);
        return is_string($value) ? trim($this->f3->clean($value)) : '';
    }

    protected function postRaw(string $key): string
    {
        $value = $this->f3->get('POST.' . $key);
        return is_string($value) ? trim($value) : '';
    }

    protected function validateRequired(array $fields): void
    {
        foreach ($fields as $field => $label) {
            if ($this->postRaw($field) === '') {
                $this->setError($field, "فیلد «{$label}» الزامی است.");
            }
        }
    }

    protected function validateLength(string $field, string $label, int $min, int $max): void
    {
        $value = $this->postRaw($field);
        if ($value === '') {
            return;
        }
        $len = mb_strlen($value);
        if ($len < $min || $len > $max) {
            $this->setError($field, "فیلد «{$label}» باید بین {$min} تا {$max} کاراکتر باشد.");
        }
    }

    protected function validateInt(string $field, string $label, ?int $min = null, ?int $max = null): int
    {
        $value = $this->postRaw($field);
        if ($value === '' || !ctype_digit(ltrim($value, '-'))) {
            $this->setError($field, "فیلد «{$label}» باید عدد صحیح باشد.");
            return 0;
        }
        $int = (int)$value;
        if ($min !== null && $int < $min) {
            $this->setError($field, "فیلد «{$label}» نباید کمتر از {$min} باشد.");
            return $int;
        }
        if ($max !== null && $int > $max) {
            $this->setError($field, "فیلد «{$label}» نباید بیشتر از {$max} باشد.");
            return $int;
        }
        return $int;
    }

    protected function validateDate(string $field, string $label): string
    {
        $value = $this->postRaw($field);
        if ($value === '') {
            $this->setError($field, "فیلد «{$label}» الزامی است.");
            return '';
        }
        $mysql = \App\Helpers\JalaliDate::parse($value);
        if ($mysql === null) {
            $this->setError($field, "فیلد «{$label}» باید تاریخ شمسی معتبر باشد (مثل ۱۴۰۳/۰۹/۱۳).");
            return '';
        }
        return $mysql;
    }

    protected function validateIn(string $field, string $label, array $allowed): string
    {
        $value = $this->postRaw($field);
        if (!in_array($value, $allowed, true)) {
            $this->setError($field, "مقدار فیلد «{$label}» معتبر نیست.");
            return '';
        }
        return $value;
    }

    protected function validateExists(string $field, string $label, string $modelKey, string $method): void
    {
        $value = $this->postRaw($field);
        if ($value === '' || !ctype_digit($value)) {
            return; // required rule already covers it
        }
        $models = $this->f3->get('models');
        $result = $models[$modelKey]->$method((int)$value);
        $exists = $result instanceof \DB\SQL\Mapper ? !$result->dry() : $result !== null;
        if (!$exists) {
            $this->setError($field, "مقدار انتخاب‌شده برای «{$label}» وجود ندارد.");
        }
    }

    /**
     * Re-render the form dialog with validation errors, old input,
     * and a Win98-style alert box.
     */
    protected function renderWithErrors(string $view, array $data): void
    {
        $old = [];
        foreach ($this->f3->get('POST') as $key => $value) {
            if (is_string($value)) {
                $old[$key] = $value;
            }
        }

        $this->render($view, $data + [
            'errors' => $this->errors,
            'old' => $old,
        ], 'layout.htm');
    }
}
