<?php

namespace lx;

/**
 * Interface ResponseInterface
 * @package lx
 */
interface ResponseInterface extends ErrorCollectorInterface
{
    public function applyResponseParams();

    /**
     * @return int
     */
    public function getCode();

    /**
     * @return mixed
     */
    public function getData();

    /**
     * @return string
     */
    public function getDataString();

    /**
     * @return string
     */
    public function getType();
}
