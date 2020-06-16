<?php

namespace lx;

/**
 * Interface UserInterface
 * @package lx
 */
interface UserInterface
{
    /**
     * @return bool
     */
    public function isGuest();

    /**
     * @return bool
     */
    public function isAvailable();

    /**
     * @param string $name
     */
    public function setAuthFieldName($name);

    /**
     * @return string
     */
    public function getAuthFieldName();

    /**
     * @return string
     */
    public function getAuthField();
}
