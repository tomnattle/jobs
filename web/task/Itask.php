<?php
namespace task;

interface Itask
{

    public function setName($name);

    public function loadInfo($uuid);

    public function loadReady();

    public function beginProcess($uuid);

    public function updateProcessRate($uuid, $rate);

    public function finished($uuid);

    public function saveResult($uuid, $result);

    public function abort($uuid, $abort_reason);
}

