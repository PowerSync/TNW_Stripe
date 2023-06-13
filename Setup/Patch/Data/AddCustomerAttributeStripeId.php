<?php

declare(strict_types=1);

namespace TNW\Stripe\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetup;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Api\Data\AttributeSetInterfaceFactory;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Sales\Setup\SalesSetupFactory;

class AddCustomerAttributeStripeId implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $setup;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var CustomerSetupFactory
     */
    private $customerSetupFactory;

    /**
     * @var AttributeSetInterfaceFactory
     */
    private $attributeSetFactory;

    /**
     * @param ModuleDataSetupInterface $setup
     * @param EavSetupFactory $eavSetupFactory
     * @param CustomerSetupFactory $customerSetupFactory
     * @param AttributeSetInterfaceFactory $attributeSetFactory
     */
    public function __construct(ModuleDataSetupInterface $setup, EavSetupFactory $eavSetupFactory, CustomerSetupFactory $customerSetupFactory, AttributeSetInterfaceFactory $attributeSetFactory)
    {
        $this->setup = $setup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
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

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->setup]);
        $eavSetup->removeAttribute(Customer::ENTITY, 'stripe_id');
        /** @var CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->setup]);

        $customerEntity = $customerSetup->getEavConfig()->getEntityType('customer');
        $attributeSetId = $customerEntity->getDefaultAttributeSetId();

        /** @var $attributeSet AttributeSet */
        $attributeSet = $this->attributeSetFactory->create();
        $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);

        $customerSetup->addAttribute(Customer::ENTITY, 'stripe_id', ['type' => 'varchar', 'label' => 'Stripe Id', 'input' => 'text', 'required' => false, 'visible' => true, 'user_defined' => true, 'sort_order' => 1000, 'position' => 1000, 'system' => 0,]);
        //add attribute to attribute set
        $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'stripe_id')->addData(['attribute_set_id' => $attributeSetId, 'attribute_group_id' => $attributeGroupId, 'used_in_forms' => ['adminhtml_customer'],])->save();

        $this->setup->endSetup();
    }

    public function revert()
    {
        // TODO: Implement revert() method.
    }
}
