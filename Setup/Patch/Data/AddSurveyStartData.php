<?php

declare(strict_types=1);

namespace TNW\Stripe\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddSurveyStartData implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $setup;

    public function __construct(
        ModuleDataSetupInterface $setup
    ) {
        $this->setup = $setup;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $this->setup->startSetup();
        $table = $this->setup->getTable('core_config_data');
        $this->setup->getConnection()->insert(
            $table,
            [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'tnw_module-stripe/survey/start_date',
                'value' => date_create()->modify('+7 day')->getTimestamp()
            ]
        );
        $this->setup->endSetup();
    }
}
