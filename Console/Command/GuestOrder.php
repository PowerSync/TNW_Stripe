<?php
namespace TNW\Stripe\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TNW\Stripe\Model\Order\Guest;

/**
 * Class GuestOrder
 */
class GuestOrder extends Command
{
    /**
     * @var Guest
     */
    protected $guest;

    /**
     * @var State
     */
    protected $appState;

    /**
     * GuestOrder constructor.
     * @param Guest $guest
     * @param State $appState
     * @param string|null $name
     */
    public function __construct(
        Guest $guest,
        State $appState,
        string $name = null
    ) {
        $this->guest = $guest;
        $this->appState = $appState;
        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('tnw:exportguestorders');
        $this->setDescription('Export Guest Orders to Stripe');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->appState->emulateAreaCode(Area::AREA_FRONTEND, [$this, 'export'], [$input, $output]);
    }

    /**
     * @param $input
     * @param $output
     */
    public function export($input, $output)
    {
        $response = $this->guest->exportGuestOrders();
        if (!empty($response) && is_array($response)) {
            $output->writeln("There were errors on Guest orders export to Stripe: ");
                print_r($response);
        } else {
            $output->writeln("Guest orders export to Stripe done.");
        }
    }
}
