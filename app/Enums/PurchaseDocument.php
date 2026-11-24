<?php

namespace App\Enums;

enum PurchaseDocument: string
{
    case Contract = 'contract';
    case Invoice = 'invoice';

    /** Invoice when an invoice number is present, otherwise a commission contract. */
    public static function fromInvoiceNumber(?string $invoiceNumber): self
    {
        return filled($invoiceNumber) ? self::Invoice : self::Contract;
    }

    public function label(): string
    {
        return match ($this) {
            self::Contract => 'Umowa',
            self::Invoice => 'Faktura',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Contract => 'clipboard-document-check',
            self::Invoice => 'document-text',
        };
    }

    /** Tailwind text color for the document icon. */
    public function iconClass(): string
    {
        return match ($this) {
            self::Contract => 'text-emerald-500',
            self::Invoice => 'text-blue-500',
        };
    }

}