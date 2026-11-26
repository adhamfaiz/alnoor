<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Al-Noor Grocery (بقالة النور) Invoice API',
    description: 'REST API to record and manage invoices for Al-Noor Grocery. Totals (subtotal, 15% tax, total) are computed strictly on the server in YER.'
)]
#[OA\Server(
    url: '/',
    description: 'Current API Server'
)]
#[OA\Tag(
    name: 'Invoices',
    description: 'Create, retrieve, and search grocery invoices'
)]
abstract class Controller
{
    //
}
