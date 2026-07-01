<?php

namespace SuiteZap\LawFirm\Financial\Services;

use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;
use SuiteZap\LawFirm\SaaS\Services\SuiteCoinService;

/**
 * ExchangeRateService — Taxa de câmbio soberana via MotherShip.
 *
 * Regras de Negócio:
 *   - O LawFirm NÃO calcula PTAX nem aplica markdown interno.
 *   - O `consumer_rate` retornado pelo MotherShip é a única fonte de verdade.
 *   - Conversão USD→BRL usa exclusivamente este campo.
 *   - Conversão BRL→SuiteCoins (Ƶ) delega para SuiteCoinService.
 *
 * @since v3.47
 */
class ExchangeRateService
{
    /** Fallback PTAX comercial aproximado caso MotherShip esteja offline. */
    public const FALLBACK_CONSUMER_RATE = 5.75;

    // ══════════════════════════════════════════════════════════
    // TAXA DE CÂMBIO
    // ══════════════════════════════════════════════════════════

    /**
     * Retorna o consumer_rate soberano do MotherShip.
     * Fallback: PTAX aproximado (5.75) se o serviço estiver indisponível.
     */
    public static function getConsumerRate(): float
    {
        try {
            $data = MotherShipService::getExchangeRate();
            $rate = (float) ($data['consumer_rate'] ?? 0.0);

            return $rate > 0.0 ? $rate : self::FALLBACK_CONSUMER_RATE;
        } catch (\Throwable $e) {
            Log::error('[ExchangeRateService] Falha ao obter consumer_rate: '.$e->getMessage());

            return self::FALLBACK_CONSUMER_RATE;
        }
    }

    /**
     * Retorna o suitecoin_multiplier do MotherShip.
     * Fallback: valor padrão do SuiteCoinService (10).
     */
    public static function getSuitecoinMultiplier(): int
    {
        try {
            $data = MotherShipService::getExchangeRate();
            $mult = (int) ($data['suitecoin_multiplier'] ?? 0);

            return $mult > 0 ? $mult : (int) SuiteCoinService::DEFAULT_RATE;
        } catch (\Throwable $e) {
            Log::error('[ExchangeRateService] Falha ao obter suitecoin_multiplier: '.$e->getMessage());

            return (int) SuiteCoinService::DEFAULT_RATE;
        }
    }

    // ══════════════════════════════════════════════════════════
    // CONVERSÃO
    // ══════════════════════════════════════════════════════════

    /**
     * Converte USD para BRL usando o consumer_rate soberano.
     *
     * @param  float  $usd  Valor em dólares americanos
     * @return float Valor em reais
     */
    public static function usdToBrl(float $usd): float
    {
        return round($usd * self::getConsumerRate(), 4);
    }

    /**
     * Converte BRL para SuiteCoins (Ƶ) usando o multiplier dinâmico do MotherShip.
     *
     * @param  float  $brl  Valor em reais
     * @return float Valor em SuiteCoins
     */
    public static function brlToSuiteCoins(float $brl): float
    {
        return $brl * self::getSuitecoinMultiplier();
    }

    /**
     * Converte USD para SuiteCoins (Ƶ) — pipeline completo.
     *
     * @param  float  $usd  Valor em dólares
     * @return float Valor em SuiteCoins
     */
    public static function usdToSuiteCoins(float $usd): float
    {
        return self::brlToSuiteCoins(self::usdToBrl($usd));
    }

    // ══════════════════════════════════════════════════════════
    // FORMATAÇÃO
    // ══════════════════════════════════════════════════════════

    /**
     * Formata valor em BRL para exibição.
     * Ex: 1234.5 → "R$ 1.234,50"
     */
    public static function formatBrl(float $brl): string
    {
        return 'R$ '.number_format($brl, 2, ',', '.');
    }

    /**
     * Formata valor em SuiteCoins para exibição.
     * Delega para SuiteCoinService para consistência visual.
     */
    public static function formatSuiteCoins(float $suitecoins): string
    {
        return SuiteCoinService::format($suitecoins);
    }

    /**
     * Converte USD e retorna string formatada em SuiteCoins.
     * Útil para rendering direto em Blade/JSON.
     */
    public static function usdToFormattedSuiteCoins(float $usd): string
    {
        return self::formatSuiteCoins(self::usdToSuiteCoins($usd));
    }
}
