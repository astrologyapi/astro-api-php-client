<?php

declare(strict_types=1);

namespace AstrologyAPI\Models;

/**
 * Optional white-label branding configuration for PDF reports.
 */
final readonly class PDFBranding
{
    public function __construct(
        public ?string $logoUrl      = null,
        public ?string $companyName  = null,
        public ?string $companyInfo  = null,
        public ?string $domainUrl    = null,
        public ?string $companyEmail = null,
        public ?string $companyLandline = null,
        public ?string $companyMobile   = null,
        public ?string $footerLink   = null,
        public ?string $chartStyle   = null,
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $fields = [
            'logo_url'      => $this->logoUrl,
            'company_name'  => $this->companyName,
            'company_info'  => $this->companyInfo,
            'domain_url'    => $this->domainUrl,
            'company_email' => $this->companyEmail,
            'company_landline' => $this->companyLandline,
            'company_mobile'   => $this->companyMobile,
            'footer_link'   => $this->footerLink,
            'chart_style'   => $this->chartStyle,
        ];

        return array_filter($fields, static fn ($v) => $v !== null);
    }
}
