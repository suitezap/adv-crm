<?php

namespace SuiteZap\LawFirm\SaaS\Services;

use Illuminate\Support\Facades\Cache;

/**
 * SuiteCoinService — Central de conversão e formatação da moeda virtual SuiteCoins (Ƶ).
 *
 * Regras de Negócio:
 *   - Taxa de Câmbio: R$ 1,00 = Ƶ 10,00 (configurável via app_config)
 *   - Markup de Segurança: 25% sobre custo real API (configurável)
 *   - Arredondamento: floor() para créditos do usuário, ceil() para custos
 *   - Valor mínimo de recarga: R$ 25,00
 *
 * @since v3.47 — SuiteCoins Migration
 */
class SuiteCoinService
{
    // ══════════════════════════════════════════════════════════
    // CONSTANTES (fallback quando app_config não disponível)
    // ══════════════════════════════════════════════════════════
    public const DEFAULT_RATE = 10.0;

    public const DEFAULT_MARKUP = 1.25;

    public const DEFAULT_MIN_RECHARGE_BRL = 25.00;

    public const SYMBOL = 'Ƶ';

    public const CURRENCY_CODE = 'SUITECOIN';

    // ══════════════════════════════════════════════════════════
    // CONFIGURAÇÃO (lidas do MotherShip com cache)
    // ══════════════════════════════════════════════════════════

    /**
     * Taxa de câmbio: quantos SuiteCoins por R$ 1,00.
     */
    public static function getRate(): float
    {
        $val = MotherShipService::getAppConfig('suitecoin_rate');

        return $val !== null ? (float) $val : self::DEFAULT_RATE;
    }

    /**
     * Multiplicador de markup sobre custo real das APIs.
     */
    public static function getMarkup(): float
    {
        $val = MotherShipService::getAppConfig('suitecoin_markup');

        return $val !== null ? (float) $val : self::DEFAULT_MARKUP;
    }

    /**
     * Valor mínimo de recarga em BRL.
     */
    public static function getMinRechargeBrl(): float
    {
        $val = MotherShipService::getAppConfig('suitecoin_min_recharge_brl');

        return $val !== null ? (float) $val : self::DEFAULT_MIN_RECHARGE_BRL;
    }

    // ══════════════════════════════════════════════════════════
    // CONVERSÃO
    // ══════════════════════════════════════════════════════════

    /**
     * Converte BRL para SuiteCoins (apenas para exibição UI).
     * Ex: R$ 25,00 → Ƶ 250,00
     */
    public static function toVirtual(float $brlAmount): float
    {
        return $brlAmount * self::getRate();
    }

    /**
     * Converte SuiteCoins para BRL (para relatórios contábeis).
     */
    public static function toBrl(float $suitecoins): float
    {
        $rate = self::getRate();

        return $rate > 0 ? round($suitecoins / $rate, 2) : 0.0;
    }

    /**
     * Calcula o custo real a ser debitado do banco (em BRL).
     * Aplica apenas o Markup de Segurança.
     * Ex: Custo real R$ 0,10 → Debita R$ 0,125
     */
    public static function calculateServicePriceBrl(float $costBrl): float
    {
        return $costBrl * self::getMarkup();
    }

    /**
     * Calcula o custo de exibição de um serviço em SuiteCoins na UI.
     * Ex: Custo real R$ 0,10 → Exibe Ƶ 1,25
     */
    public static function calculateServicePriceVirtual(float $costBrl): float
    {
        return self::toVirtual(self::calculateServicePriceBrl($costBrl));
    }

    /**
     * Calcula price_virtual de um Assistente Jurídico em BRL (armazenado no DB).
     * Fórmula: ceil(base × markup × 10000) / 10000 — 4 casas de precisão.
     *
     * @param  float  $baseCostBrl  Custo técnico base (tokens LLM + infra)
     * @param  float  $markup  Multiplicador de markup (padrão 1.25 = +25%)
     * @return float Preço em BRL a ser debitado do suitecoin_balance
     */
    public static function calculateAssistantPriceBrl(float $baseCostBrl, float $markup = self::DEFAULT_MARKUP): float
    {
        if ($baseCostBrl <= 0.0) {
            return 0.0;
        }

        return ceil($baseCostBrl * $markup * 10000) / 10000;
    }

    /**
     * Calcula o custo de exibição em Ƶ de um Assistente.
     * Ex: base R$ 0,10 markup 1.25 → Ƶ 1,25
     */
    public static function calculateAssistantPriceVirtual(float $baseCostBrl, float $markup = self::DEFAULT_MARKUP): float
    {
        return self::toVirtual(self::calculateAssistantPriceBrl($baseCostBrl, $markup));
    }

    // ══════════════════════════════════════════════════════════
    // VALIDAÇÃO
    // ══════════════════════════════════════════════════════════

    /**
     * Verifica se o saldo BRL no banco é suficiente para cobrir o custo em BRL.
     */
    public static function hasSufficientBalance(float $balanceBrl, float $costBrl): bool
    {
        return $balanceBrl >= $costBrl;
    }

    /**
     * Valida se o valor de recarga é permitido (>= mínimo).
     */
    public static function isValidRechargeAmount(float $brlAmount): bool
    {
        return $brlAmount >= self::getMinRechargeBrl();
    }

    // ══════════════════════════════════════════════════════════
    // FORMATAÇÃO
    // ══════════════════════════════════════════════════════════

    /**
     * Formata valor para exibição na UI.
     *
     * Ex: 250.5 → "Ƶ 250,50"
     */
    public static function format(float $virtualAmount): string
    {
        return self::SYMBOL.' '.number_format($virtualAmount, 2, ',', '.');
    }

    /**
     * Formata saldo armazenado em BRL para o formato visual (Ƶ).
     */
    public static function formatFromBrl(float $brlAmount): string
    {
        return self::format(self::toVirtual($brlAmount));
    }

    public static function insufficientBalanceMessage(float $balanceBrl, float $costBrl): string
    {
        return sprintf(
            'Saldo insuficiente. Disponível: %s | Necessário: %s',
            self::formatFromBrl($balanceBrl),
            self::format(self::calculateServicePriceVirtual($costBrl / self::getMarkup()))
        );
    }

    /**
     * Tooltip informativo para o símbolo Ƶ.
     */
    public static function tooltip(): string
    {
        $rate = (int) self::getRate();

        return 'SuiteCoins (Ƶ): Suas moedas virtuais para uso de Inteligência Artificial. '
             ."Cada R$ 1,00 investido gera Ƶ {$rate},00 créditos, garantindo a melhor cotação "
             .'de processamento para seus documentos.';
    }
}
