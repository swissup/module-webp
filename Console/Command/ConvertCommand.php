<?php
declare(strict_types=1);

namespace Swissup\Webp\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\Catalog\Model\Config\CatalogMediaConfig;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\ProgressBarFactory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConvertCommand extends \Symfony\Component\Console\Command\Command
{
    const SKIP_HIDDEN_IMAGES = 'skip_hidden_images';

    const DEFAULT_LIMIT = 100000;

    const CONFIG_XML_PATH_CATALOG_MEDIA_URL_FORMAT = 'web/url/catalog_media_url_format';

    /**
     * @var State
     */
    private $appState;

    /**
     * @var \Swissup\Webp\Model\ImageConvert
     */
    private $imageConvert;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ProgressBarFactory
     */
    private $progressBarFactory;

    /**
     * @param State $appState
     * @param \Swissup\Webp\Model\ImageConvert $imageConvert
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param ProgressBarFactory $progressBarFactory
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        State $appState,
        \Swissup\Webp\Model\ImageConvert $imageConvert,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        ProgressBarFactory $progressBarFactory
    ) {
        parent::__construct();
        $this->appState = $appState;
        $this->imageConvert = $imageConvert;
        $this->scopeConfig = $scopeConfig;
        $this->progressBarFactory = $progressBarFactory;
    }

    /**
     * @inheritdoc
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
            new InputOption(
                'limit',
                'l',
                InputArgument::OPTIONAL,
                'limit --limit=10 (default: 100 000)',
                self::DEFAULT_LIMIT
            ),
            new InputOption(
                'filename',
                'f',
                InputArgument::OPTIONAL,
                'filename filter --filename=1.png'
            )
        ];
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->isImageOptimizationBasedOnQueryParams()) {
            $message = 'Your Magento is not configured to use "Image optimization based on query parameters".'
                . ' So running this command no more sense.'
                . "\n" . 'Read more "Catalog media URL format" '
                . '- https://experienceleague.adobe.com/docs/commerce-admin/config/general/web.html';
            $output->writeln("<error><fg=red;options=bold>Error: {$message}</></error>");
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        $skipHiddenImages = (bool) $input->getOption(self::SKIP_HIDDEN_IMAGES);
        $limit = (int) $input->getOption('limit');
        $filename = (string) $input->getOption('filename');
        if (!empty($filename)) {
            $this->imageConvert->setFilenameFilter($filename);
        }
        try {
            $errors = [];
            $this->appState->setAreaCode(Area::AREA_GLOBAL);
            $generator = $this->imageConvert
                ->setSkipHiddenImages($skipHiddenImages)
                ->setLimit($limit)
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

    /**
     * Check is web server image optimisation was enabled
     *
     * @return boolean
     */
    private function isImageOptimizationBasedOnQueryParams()
    {
        $catalogMediaUrlFormat = $this->scopeConfig->getValue(
            self::CONFIG_XML_PATH_CATALOG_MEDIA_URL_FORMAT
            //\Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        return $catalogMediaUrlFormat === CatalogMediaConfig::IMAGE_OPTIMIZATION_PARAMETERS;
    }
}
