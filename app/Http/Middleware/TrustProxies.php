<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Fideloper\Proxy\TrustProxies as Middleware;

class TrustProxies extends Middleware
{
    protected $proxies = '*'; // barcha proxy’larni ishonchli deb belgilaydi

    protected $headers = Request::HEADER_X_FORWARDED_ALL;
}
