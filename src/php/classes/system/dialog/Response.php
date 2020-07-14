<?php

namespace lx;

use lx;

/**
 * Class Response
 * @package lx
 */
class Response implements ResponseInterface
{
    use ErrorCollectorTrait;

    /** @var int */
    private $code;

    /** @var string */
    private $type;

    /** @var mixed */
    private $data;

    /** @var mixed */
    private $fullData;

    /**
     * Response constructor.
     * @param mixed $data
     * @param int $code
     */
    public function __construct($data, $code = ResponseCodeEnum::OK)
    {
        $this->code = $code;

        if ($data !== null) {
            if ($code == ResponseCodeEnum::OK) {
                $this->data = $data;
            } else {
                $this->addError($data);
            }
        }
    }
    
    public function applyResponseParams()
    {
        http_response_code($this->getCode());
        header("Content-Type: text/{$this->getType()}; charset=utf-8");
    }

    /**
     * @return int
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return bool
     */
    public function isSuccessfull()
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

    /**
     * @return string
     */
    public function getDataString()
    {
        $data = $this->getFullData();
        $type = $this->getType();
        if ($type == 'html' || $type == 'plane') {
            return $data;
        }

        //TODO сериалайзер
        if ($this->getType() == 'json') {
            $result = $this->isSuccessfull()
                ? $data
                : [
                    'success' => false,
                    'error_code' => $this->getCode(),
                    'error_details' => $data,
                ];

            return json_encode($result);
        }

        return '';
    }

    /**
     * @return string
     */
    public function getType()
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
     * @return mixed
     */
    private function getFullData()
    {
        if (!isset($this->fullData)) {
            if ($this->hasErrors()) {
                $data = [];
                /** @var ErrorCollectorError $error */
                foreach ($this->getErrors() as $error) {
                    $data[] = $error->getInfo()['description'];
                }
                $this->fullData = $data;
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
