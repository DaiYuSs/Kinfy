<?php

namespace Kinfy\View;

interface IView
{
    public function show($tpl);

    public function set($name, $value);

    public function setTheme($value);

    public function setSuffix($value);
}
