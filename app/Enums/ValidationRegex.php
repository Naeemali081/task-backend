<?php

namespace App\Enums;

enum ValidationRegex : string
{
    case PHONE = "/^(?:\+91|91|0)?[6789]\d{9}$/";
}


