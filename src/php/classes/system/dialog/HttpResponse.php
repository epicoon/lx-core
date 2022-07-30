<?php

namespace lx;

use lx;

class HttpResponse implements HttpResponseInterface
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

    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    public function setCode(int $code): void
    {
        $this->code = $code;
    }

    /**
     * @param mixed $data
     */
    public function setData($data): void
    {
        $this->data = $data;
    }

    public function setWarning(): void
    {
        $this->isWarning = true;
    }

    public function isForbidden(): bool
    {
        return $this->getCode() == ResponseCodeEnum::FORBIDDEN;
    }

    public function requireAuthorization(): bool
    {
        return $this->getCode() == ResponseCodeEnum::UNAUTHORIZED;
    }

    public function isSuccessfull(): bool
    {
        return $this->getCode() == ResponseCodeEnum::OK;
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
                case lx::$app->request->isPageLoad():
                    $this->type = 'html';
                    break;
                case lx::$app->request->isAssetLoad():
                    $this->type = 'plane';
                    break;
                case lx::$app->request->isAjax():
                case lx::$app->request->isCors():
                default:
                    $this->type = 'json';
            }
        }

        return $this->type;
    }

    public function beforeSend(): void
    {
        http_response_code($this->getCode());
        header("Content-Type: text/{$this->getDataType()}; charset=utf-8");
    }

    public function send(): void
    {
        $this->beforeSend();
        $this->echo($this->getDataAsString());
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * PRIVATE
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    private function echo(string $data): void
    {
        ignore_user_abort(true);
        set_time_limit(0);
        ob_start(/*'ob_gzhandler'*/);
        echo $data;
        header('Connection: close');
        header('Content-Length: ' . ob_get_length());
        ob_end_flush();
        ob_flush();
        flush();
        if (session_id()) session_write_close();
        fastcgi_finish_request();
    }

    private function getDump(): string
    {
        /** @var HttpRequest $request */
        $request = lx::$app->request;
        if (!$request) {
            return '';
        }

        if (!$request->isPageLoad() && !$request->isAjax()) {
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
