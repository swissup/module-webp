<?php
declare(strict_types=1);

namespace Swissup\Webp\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\ProgressBarFactory;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class ConvertCommand extends \Symfony\Component\Console\Command\Command
{
    const SKIP_HIDDEN_IMAGES = 'skip_hidden_images';

    /**
     * @var \Swissup\Webp\Model\ImageConvert
     */
    private $imageConvert;

    /**
     * @var State
     */
    private $appState;

    /**
     * @var ProgressBarFactory
     */
    private $progressBarFactory;

    /**
     * @param State $appState
     * @param \Swissup\Webp\Model\ImageConvert $imageConvert
     * @param ProgressBarFactory $progressBarFactory
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        State $appState,
        \Swissup\Webp\Model\ImageConvert $imageConvert,
        ProgressBarFactory $progressBarFactory
    ) {
        parent::__construct();
        $this->appState = $appState;
        $this->imageConvert = $imageConvert;
        $this->progressBarFactory = $progressBarFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('swissup:webp:convert')
            ->setDescription('Convert product images to webp')
            ->setAliases(['webp:convert'])
            ->setDefinition($this->getOptionsList());
        ;
    }

    /**
     * Image resize command options list
     *
     * @return array
     */
    private function getOptionsList() : array
    {
        return [
            new InputOption(
                self::SKIP_HIDDEN_IMAGES,
                null,
                InputOption::VALUE_NONE,
                'Do not process images marked as hidden from product page'
            ),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $skipHiddenImages = (bool) $input->getOption(self::SKIP_HIDDEN_IMAGES);
        try {
            $errors = [];
            $this->appState->setAreaCode(Area::AREA_GLOBAL);
            $generator = $this->imageConvert
                ->setSkipHiddenImages($skipHiddenImages)
                ->execute();

            /** @var ProgressBar $progress */
            $progress = $this->progressBarFactory->create(
                [
                    'output' => $output,
                    'max' => $generator->current()
                ]
            );
            $progress->setFormat(
                "%current%/%max% [%bar%] %percent:3s%% %elapsed% %memory:6s% \t| <info>%message%</info>"
            );

            if ($output->getVerbosity() !== OutputInterface::VERBOSITY_NORMAL) {
                $progress->setOverwrite(false);
            }

            while ($generator->valid()) {
                $resizeInfo = $generator->key();
                $error = $resizeInfo['error'];
                $filename = $resizeInfo['filename'];

                if ($error !== '') {
                    $errors[$filename] = $error;
                }

                $progress->setMessage($filename);
                $progress->advance();
                $generator->next();
            }
        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            // we must have an exit code higher than zero to indicate something was wrong
            return Cli::RETURN_FAILURE;
        }

        $output->write(PHP_EOL);
        if (count($errors)) {
            $output->writeln("<info>Product images converted with errors:</info>");
            foreach ($errors as $error) {
                $output->writeln("<error>{$error}</error>");
            }
        } else {
            $output->writeln("<info>Product images converted successfully</info>");
        }

        return Cli::RETURN_SUCCESS;
    }
}
