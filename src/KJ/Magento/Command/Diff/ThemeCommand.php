<?php

namespace KJ\Magento\Command\Diff;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ThemeCommand extends AbstractCommand
{
    protected $_version = null;

    protected function configure()
    {
        parent::configure();
        $this
            ->setName('diff:theme')
            ->addArgument('current-theme', InputArgument::REQUIRED, 'Current theme', null)
            ->addArgument('theme-to-compare-against', InputArgument::REQUIRED, 'Which theme to diff against', null)
            ->addArgument('pattern', InputArgument::OPTIONAL, 'List details for any file with a diff that matches on this pattern')
            ->addOption('lines', null, InputOption::VALUE_OPTIONAL, 'The number of lines of context in the diff', 3)
            ->addOption('pro', null, InputOption::VALUE_OPTIONAL, 'If this parameter is passed, it will check pro instead of ee')
            ->setDescription('Diff the theme to detect changes.');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_input = $input;
        $this->_output = $output;

        $this->initMagento();
        $this->_info(sprintf('Magento Version is %s', $version));
        $this->_info(sprintf('Comparing current theme %s to %s', $this->_getCurrentTheme(), $this->_getThemeToCompareAgainst()));

        $comparison = new \KJ\Magento\Util\ThemeComparison($input, $output);

        if ($this->_input->getOption('lines')) {
            $comparison->setLinesOfContext($this->_input->getOption('lines'));
        }

        $comparison->setMagentoInstanceRootDirectory($this->_magentoRootFolder)
            ->setCurrentTheme($this->_getCurrentTheme())
            ->setThemeToCompareAgainst($this->_getThemeToCompareAgainst())
            ->compare();

        if ($input->getArgument('pattern')) {
            $this->_outputComparisonDetails($comparison);
        } else {
            $this->_outputComparisonSummary($comparison);
        }
    }

    protected function _getCurrentTheme()
    {
        return $this->_input->getArgument('current-theme');
    }

    protected function _getThemeToCompareAgainst()
    {
        return $this->_input->getArgument('theme-to-compare-against');
    }

    /**
     * @param $comparison \KJ\Magento\Util\ThemeComparison
     */
    protected function _outputComparisonSummary($comparison)
    {
        $this->getHelper('table')
            ->setHeaders(array('File'))
            ->setRows($comparison->getSummary())
            ->render($this->_output);
    }

    /**
     * @param $comparison \KJ\Magento\Util\Comparison
     */
    protected function _outputComparisonDetails($comparison)
    {
        /** @var $comparisonItem \KJ\Magento\Util\Comparison\Item */
        foreach ($comparison->getChangedFiles() as $comparisonItem) {
            if ($comparisonItem->matchPattern($this->_input->getArgument('pattern'))) {
                $this->writeSection($this->_output, $comparisonItem->getFileName());
                $this->_output->write($comparisonItem->getDiff(), true);
            }
        }
    }
}