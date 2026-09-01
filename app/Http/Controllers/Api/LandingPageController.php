<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LayoutSectionResource;
use App\Services\LandingPageServiceInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LandingPageController extends Controller
{
    public function __construct(
        protected LandingPageServiceInterface $landingPageService
    ) {}

    /**
     * GET /api/landing-page
     */
    public function index(): AnonymousResourceCollection
    {
        return LayoutSectionResource::collection($this->landingPageService->getLayout());
    }
}
