<?php
namespace Swissup\Webp\Model;

use Symfony\Component\Process\ExecutableFinder;
use WebPConvert\Convert\Converters\Stack as WebPConvertors;
use WebPConvert\WebPConvert;

class GetConvertors
{
    /**
     * @var array|null
     */
    private $convertors;

    public function execute()
    {
        return $this->getConvertors();
    }

    private function getConvertors($force = false)
    {
        if ($this->convertors === null || $force) {
            $this->convertors = $this->checkConvertorStatuses();
        }
        return $this->convertors;
    }

    private function checkConvertorStatuses()
    {
        $converters = WebPConvertors::getAvailableConverters();
        $finder = new ExecutableFinder();
        $dataset = [];

        $extensions = get_loaded_extensions();

        foreach ($converters as $converter) {
            $binaryName = $converter;
            $isPhpExtension = in_array($converter, $extensions);
            if ($isPhpExtension) {
                $binaryPath = "{$converter} PHP extension";
            } else {
                $binaryPath = $finder->find($binaryName);
            }

            $dataset[] = [
                'name' => $binaryName,
                'path' => $binaryPath,
                'status' => !empty($binaryPath)
            ];
        }
        return $dataset;
    }
}
