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
    /** @var array|string */
    private $fullData;

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
        $data = $this->getFullData();
        $type = $this->getDataType();
        if ($type == 'html' || $type == 'plane') {
            return $data;
        }

        //TODO сериалайзер
        if ($this->getDataType() == 'json') {
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

            return json_encode($result);
        }

        return '';
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

    /**
     * @return array|string
     */
    private function getFullData()
    {
        if (!isset($this->fullData)) {
            if (!$this->isSuccessfull()) {
                $this->fullData = $this->data;
            } else {
                $data = $this->data;
                $dump = lx::getDump();
                if ($dump != '') {
                    if (is_array($data)) {
                        $data['lxdump'] = $dump;
                    } else {
                        if (lx::$app->dialog->isAjax()) {
                            $data .= '<lx-var-dump>' . $dump . '</lx-var-dump>';
                        } else {
                            $dumpStr = '<pre class="lx-var-dump">' . $dump . '</pre>';
                            if (preg_match('/<body>/', $data)) {
                                $data = preg_replace('/<body>/', '<body>' . $dumpStr, $data);
                            } else {
                                $data = "$dumpStr$data";
                            }
                        }
                    }
                }
                $this->fullData = $data;
            }
        }

        return $this->fullData;
    }
}
