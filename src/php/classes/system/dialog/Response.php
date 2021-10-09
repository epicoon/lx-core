<?php

namespace lx;

use lx;

class Response implements ResponseInterface
{
    private int $code;
    private bool $isWarning = false;
    private string $type;
    /** @var mixed */
    private $data;

    /**
     * @param mixed $data
     */
    public function __construct($data, int $code = ResponseCodeEnum::OK)
    {
        $this->code = $code;
        $this->data = $data;
    }

    public function setWarning(): void
    {
        $this->isWarning = true;
    }

    public function beforeSend(): void
    {
        http_response_code($this->getCode());
        header("Content-Type: text/{$this->getDataType()}; charset=utf-8");
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function isForbidden(): bool
    {
        return $this->getCode() == ResponseCodeEnum::FORBIDDEN;
    }

    public function isSuccessfull(): bool
    {
        return $this->getCode() == ResponseCodeEnum::OK;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    public function getDataAsString(): string
    {
        $data = $this->data;
        switch ($this->getDataType()) {
            case 'html':
            case 'plane':
                $dump = $this->getDump();
                $result = ($dump === '')
                    ? $data
                    : $this->insertDump($data, $dump);
                break;
            case 'json':
                $result = $this->isSuccessfull()
                    ? [
                        'success' => !$this->isWarning,
                        'data' => $data,
                    ]
                    : [
                        'success' => false,
                        'error_code' => $this->getCode(),
                        'error_details' => $data,
                    ];
                $result = json_encode($result) . $this->getDump();
                break;
            default:
                $result = '';
        }
        return $result;
    }

    public function getDataType(): string
    {
        if (!isset($this->type)) {
            switch (true) {
                case lx::$app->dialog->isPageLoad():
                    $this->type = 'html';
                    break;
                case lx::$app->dialog->isAssetLoad():
                    $this->type = 'plane';
                    break;
                case lx::$app->dialog->isAjax():
                case lx::$app->dialog->isCors():
                default:
                    $this->type = 'json';
            }
        }

        return $this->type;
    }
    
    private function getDump(): string
    {
        /** @var Dialog $dialog */
        $dialog = lx::$app->dialog;
        if (!$dialog) {
            return '';
        }

        if (!$dialog->isPageLoad() && !$dialog->isAjax()) {
            return '';
        }

        $dump = lx::getDump();
        if ($dump === '') {
            return '';
        }
        return '<pre class="lx-var-dump">' . $dump . '</pre>';
    }

    private function insertDump(string $data, string $dump): string
    {
        return (preg_match('/<body>/', $data))
            ? preg_replace('/<body>/', '<body>' . $dump, $data)
            : "$dump$data";
    }
}
