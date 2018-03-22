<?php

namespace TNW\Stripe\Controller\Window;

use Magento\Framework\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\RawFactory;

class Close extends Action\Action
{
    /**
     * @var RawFactory
     */
    private $rawFactory;

    public function __construct(
        Action\Context $context,
        RawFactory $rawFactory
    ) {
        parent::__construct($context);
        $this->rawFactory = $rawFactory;
    }

    /**
     * Execute action based on request and return result
     *
     * Note: Request will be added as operation argument in future
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        return $this->rawFactory->create()
            ->setContents('<script type="text/javascript">window.parent.require(\'TNW_Stripe/js/featherlight\').current().close()</script>');
    }
}
