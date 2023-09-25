<?php
declare(strict_types=1);

namespace Swissup\Webp\Console\Command;

use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class CheckCommand extends \Symfony\Component\Console\Command\Command
{
    const INPUT_OPTION_AVAILABLE_SHORTCUT = 'a';
    const INPUT_OPTION_AVAILABLE = 'available';

    /**
     *
     * @var \Swissup\Webp\Model\GetConvertors
     */
    private $getConvertors;

    /**
     * Inject dependencies
     *
     * @param \Swissup\Webp\Model\GetConvertors $getConvertors
     */
    public function __construct(\Swissup\Webp\Model\GetConvertors $getConvertors)
    {
        parent::__construct();
        $this->getConvertors = $getConvertors;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('swissup:webp:check')
            ->setDescription('Check available webp convertors.')
            ->setAliases(['webp:check', 'webp:convert:check'])
        ;
        $this->addOption(
            self::INPUT_OPTION_AVAILABLE,
            self::INPUT_OPTION_AVAILABLE_SHORTCUT,
            InputOption::VALUE_NONE,
            'Show only available'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $available = (bool) $input->getOption(self::INPUT_OPTION_AVAILABLE);
        $rows = [];
        foreach ($this->getConvertors->execute() as $data) {
            $binaryName = $data['name'];
            $binaryPath = $data['path'];
            $color = 'green';
            if (!$data['status']) {
                if ($available) {
                    continue;
                }
                $binaryPath = "{$binaryName} is not found";
                $color = 'red';
            }
            $binaryPath = "<fg={$color}>{$binaryPath}</>";
            $rows[] = [$binaryName, $binaryPath];
        }

        $table = new Table($output);
        $table->setHeaders(['Converter', 'Path'])
            ->setRows($rows);
        $table->render();

        return Cli::RETURN_SUCCESS;
    }
}
