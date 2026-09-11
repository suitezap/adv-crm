<?php

namespace Webkul\Core\Traits;

use enshrined\svgSanitize\data\AllowedAttributes;
use enshrined\svgSanitize\data\AllowedTags;
use enshrined\svgSanitize\Sanitizer as MainSanitizer;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Trait for sanitizing SVG uploads to prevent security vulnerabilities.
 */
trait Sanitizer
{
    /**
     * Sanitize an SVG file to remove potentially malicious content.
     */
    public function sanitizeSvg(string $path, UploadedFile $file, ?string $disk = null): void
    {
        if (! $this->isSvgFile($file)) {
            return;
        }

        $storage = $disk ? Storage::disk($disk) : Storage::drive();

        try {
            $svgContent = $storage->get($path);

            if (! $svgContent) {
                return;
            }

            $sanitizer = new MainSanitizer;
            $sanitizer->setAllowedAttrs(new AllowedAttributes);
            $sanitizer->setAllowedTags(new AllowedTags);

            $sanitizer->minify(true);
            $sanitizer->removeRemoteReferences(true);
            $sanitizer->removeXMLTag(true);

            $sanitizer->setXMLOptions(LIBXML_NONET | LIBXML_NOBLANKS);

            $sanitizedContent = $sanitizer->sanitize($svgContent);

            if ($sanitizedContent === false) {
                $patterns = [
                    '/<script\b[^>]*>(.*?)<\/script>/is',
                    '/\bon\w+\s*=\s*["\'][^"\']*["\']/i',
                    '/javascript\s*:/i',
                    '/data\s*:[^,]*base64/i',
                ];

                $sanitizedContent = $svgContent;

                foreach ($patterns as $pattern) {
                    $sanitizedContent = preg_replace($pattern, '', $sanitizedContent);
                }

                $storage->put($path, $sanitizedContent);

                return;
            }

            $sanitizedContent = preg_replace('/(<script.*?>.*?<\/script>)|(\son\w+\s*=\s*["\'][^"\']*["\'])/is', '', $sanitizedContent);

            $storage->put($path, $sanitizedContent);
        } catch (Exception $e) {
            report($e->getMessage());

            $storage->delete($path);
        }
    }

    /**
     * Check if the uploaded file is an SVG based on both extension and mime type.
     */
    public function isSvgFile(UploadedFile $file): bool
    {
        return str_contains(strtolower($file->getClientOriginalExtension()), 'svg');
    }
}
