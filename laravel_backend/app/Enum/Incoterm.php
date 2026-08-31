<?php

namespace App\Enum;

/** This is use for international commercial terms **/
enum Incoterm: string
{
    case EXW = 'EXW';
    case FCA = 'FCA';
    case CPT = 'CPT';
    case CIP = 'CIP';
    case DAP = 'DAP';
    case DPU = 'DPU';
    case DDP = 'DDP';
    case FAS = 'FAS';
    case FOB = 'FOB';
    case CFR = 'CFR';
    case CIF = 'CIF';

    public static function label(string $value): string
    {
        return match ($value) {
            self::EXW->value => 'Ex Works',
            self::FCA->value => 'Free Carrier',
            self::CPT->value => 'Carriage Paid To',
            self::CIP->value => 'Carriage & Insurance Paid To',
            self::DAP->value => 'Delivered At Place',
            self::DPU->value => 'Delivered at Place Unloaded',
            self::DDP->value => 'Delivered Duty Paid',
            self::FAS->value => 'Free Alongside Ship',
            self::FOB->value => 'Free On Board',
            self::CFR->value => 'Cost & Freight',
            self::CIF->value => 'Cost, Insurance & Freight',
        };
    }

    public static function options(): array
    {
        return [
            self::EXW->value => self::label(self::EXW->value),
            self::FCA->value => self::label(self::FCA->value),
            self::CPT->value => self::label(self::CPT->value),
            self::CIP->value => self::label(self::CIP->value),
            self::DAP->value => self::label(self::DAP->value),
            self::DPU->value => self::label(self::DPU->value),
            self::DDP->value => self::label(self::DDP->value),
            self::FAS->value => self::label(self::FAS->value),
            self::FOB->value => self::label(self::FOB->value),
            self::CFR->value => self::label(self::CFR->value),
            self::CIF->value => self::label(self::CIF->value),
        ];
    }
}
