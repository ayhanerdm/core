<?php
namespace ayhanerdm\Core\Enums;

enum ReturnTypes: string {
    case VALUE = 'value';
    case ARRAY = 'array';
    case OBJECT = 'object';
    case JSON = 'json';
}