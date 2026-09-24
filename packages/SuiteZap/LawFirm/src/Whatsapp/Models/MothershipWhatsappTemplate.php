<?php

namespace SuiteZap\LawFirm\Whatsapp\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model para templates globais de WhatsApp gerenciados pelo MotherShip.
 *
 * Lê da tabela `lawfirm_whatsapp_templates` no banco `mothership`.
 * Ações de escrita (create/update/delete) são permitidas somente pelo
 * painel administrativo do MotherShip — no CRM do tenant, este model
 * é sempre somente-leitura.
 *
 * @property int    $id
 * @property string $name         Chave única (ex: new_prazo_client)
 * @property string $title        Título legível
 * @property string $group        Grupo (prazos, agendador_adv, financeiro, ged, juridico)
 * @property string|null $info    Texto de ajuda / variáveis disponíveis
 * @property int    $rows         Altura do textarea
 * @property string $default_text Texto padrão global
 * @property bool   $is_active
 * @property int    $sort_order
 */
class MothershipWhatsappTemplate extends Model
{
    protected $connection = 'mothership';

    protected $table = 'lawfirm_whatsapp_templates';

    protected $fillable = [
        'name',
        'title',
        'group',
        'info',
        'rows',
        'default_text',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'rows'       => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Retorna apenas os templates ativos, indexados por `name`.
     * Útil para lookup O(1) no controller.
     *
     * @return \Illuminate\Support\Collection<string, self>
     */
    public static function activeIndexedByName(): \Illuminate\Support\Collection
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->keyBy('name');
    }
}
