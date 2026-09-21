<?php

namespace App\Services;

use Cloudinary\Cloudinary;

/**
 * Phase 2 stub. Nothing in Phase 1 calls this; it only proves the SDK and
 * config are wired so PDF upload can be built on top later.
 */
class CloudinaryService
{
    private ?Cloudinary $client = null;

    public function client(): Cloudinary
    {
        return $this->client ??= new Cloudinary([
            'cloud' => [
                'cloud_name' => config('services.cloudinary.cloud_name'),
                'api_key' => config('services.cloudinary.api_key'),
                'api_secret' => config('services.cloudinary.api_secret'),
            ],
            'url' => ['secure' => true],
        ]);
    }

    // TODO (Phase 2): uploadPdf(), delete(), signedDownloadUrl()
}
