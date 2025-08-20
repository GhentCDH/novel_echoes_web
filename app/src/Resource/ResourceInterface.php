<?php


namespace App\Resource;


interface ResourceInterface
{
    public function getId(): string;
    public function toJson($options = 0);
}