<?php

namespace JMS\CommandBundle\Command;

use JMS\CommandBundle\Translation\ComparisonResult;
use Symfony\Component\Console\Input\InputArgument;
use JMS\CommandBundle\Translation\CatalogueComparator;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class ExtractTranslationsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('translation:extract')
            ->setDescription('Extracts translations of your project')
            ->addArgument('locale', InputArgument::REQUIRED, 'The locale to extract routes for')
            ->addOption('overwrite-routes', null, InputOption::VALUE_NONE, 'Whether the routing files of the JMSI18nRoutingBundle should be overwritten')
            ->addOption('output-bundle', null, InputOption::VALUE_OPTIONAL, 'The bundle which the translation files should be written to (if not given catalogues will be written to the global app/Resources/translations folder).')
            ->addOption('output-format', null, InputOption::VALUE_OPTIONAL, 'The output format', 'yml')
            ->addOption('ignore-domain', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Domains that are to be ignored.')
            ->addOption('dir', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'The directories to scan. May also be a bundle notation @FooBundle.')
            ->addOption('delete', null, InputOption::VALUE_NONE, 'Whether to delete routes which have been removed')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Whether to make any actual changes to the translation file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dirs = $this->normalizePaths($input->getOption('dir'));
        if (empty($dirs)) {
            $rootDir = $this->getContainer()->getParameter('kernel.root_dir');
            $dirs[] = $rootDir;
            $dirs[] = $rootDir.'/../src';
        }

        $extractor = $this->getContainer()->get('translation.extractor');
        $catalogue = new MessageCatalogue($input->getArgument('locale'));

        foreach ($dirs as $dir) {
            $extractor->extract($dir, $catalogue);
        }

        // normalize catalogue, necessary due to some bug in the component
        // should ideally be fixed there
        $newCatalogue = new MessageCatalogue($catalogue->getLocale());
        foreach ($catalogue->all() as $domain => $messages) {
            if ('' === $domain) {
                $domain = 'messages';
            }

            $newCatalogue->add($messages, $domain);
        }
        $catalogue = $newCatalogue;

        $outputPath = $input->getOption('output-bundle');
        if (!empty($outputPath)) {
            if ('@' === $outputPath[0]) {
                $outputPath = substr($outputPath, 1);
            }

            $outputPath = $this->getApplication()->getKernel()->getBundle($outputPath)->getPath();
        } else {
            $outputPath = $this->getContainer()->getParameter('kernel.root_dir');
        }
        $outputPath .= '/Resources/translations';

        if (!is_dir($outputPath)) {
            if (false === @mkdir($outputPath, 0777, true)) {
                throw new \RuntimeException(sprintf('Could not create output directory "%s".', $outputPath));
            }
        }
        $output->writeln('<comment>Output-Path:</comment> '.$outputPath);

        $loader = $this->getContainer()->get('translation.loader');
        $existingCatalogue = new MessageCatalogue($input->getArgument('locale'));
        $loader->loadMessages($outputPath, $existingCatalogue);

        $comparator = new CatalogueComparator();
        $ignoredDomains = $input->getOption('ignore-domain');
        if (!$input->getOption('overwrite-routes')) {
            $ignoredDomains[] = 'routes';
        }
        foreach ($ignoredDomains as $domain) {
            $comparator->addIgnoredDomain($domain);
        }

        $result = $comparator->compare($existingCatalogue, $catalogue);

        if ($input->getOption('dry-run')) {
            $this->displayResult($output, $result);

            return;
        }

        // merge in existing translations
        foreach ($catalogue->all() as $domain => $messages) {
            foreach ($messages as $id => $trans) {
                if ($existingCatalogue->has($id, $domain)) {
                    $catalogue->set($id, $existingCatalogue->get($id, $domain), $domain);
                }
            }
        }

        $dumpCatalogue = new MessageCatalogue($catalogue->getLocale());
        foreach ($catalogue->all() as $domain => $messages) {
            if (isset($ignoredDomains[$domain])) {
                continue;
            }

            uksort($messages, 'strcasecmp');

            $dumpCatalogue->add($messages, $domain);
        }

        $this->getContainer()->get('translation.writer')->writeTranslations($dumpCatalogue, $input->getOption('output-format'), array(
            'path' => $outputPath,
        ));
    }

    private function displayResult(OutputInterface $output, ComparisonResult $rs)
    {
        $this->printMessages($output, 'Added Messages', $rs->getAddedMessages());
        $this->printMessages($output, 'Deleted Messages', $rs->getDeletedMessages());
    }

    private function printMessages(OutputInterface $output, $title, array $messages)
    {
        $output->writeln('');
        if (empty($messages)) {
            $output->writeln(sprintf('<comment>%s:</comment> None', $title));
        } else {
            $output->writeln(sprintf('<comment>%s:</comment>', $title));
            $output->writeln(str_repeat('=', strlen($title)));
            foreach ($messages as $domain => $tMessages) {
                $output->writeln(sprintf('<comment>%s</comment>: %s', $domain, implode(', ', array_keys($tMessages))));
            }
        }
    }

    private function normalizePaths(array $dirs)
    {
        $kernel = $this->getApplication()->getKernel();
        foreach ($dirs as $k => $v) {
            $v = str_replace(DIRECTORY_SEPARATOR, '/', $v);

            if ('@' === $v[0]) {
                $bundleName = substr($v, 1, ($pos = strpos($v, '/')) - 1);
                $v = $kernel->getBundle($bundleName)->getPath().substr($v, $pos);
            }

            if (!is_dir($v)) {
                throw new \RuntimeException(sprintf('The directory "%s" does not exist.', $v));
            }

            if (!is_readable($v)) {
                throw new \RuntimeException(sprintf('The directory "%s" is not readable.', $v));
            }

            $dirs[$k] = $v;
        }

        return $dirs;
    }
}