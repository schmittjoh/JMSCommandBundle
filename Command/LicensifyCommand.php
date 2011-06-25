<?php

namespace JMS\CommandBundle\Command;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;

class LicensifyCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('licensify');
        $this->addArgument('bundle', InputArgument::REQUIRED, 'The bundle which should be licensified.');
        $this->addOption('license', null, InputArgument::OPTIONAL, 'The license to use', 'Apache2');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $kernel = $this->getContainer()->get('kernel');
        $bundle = $kernel->getBundle($input->getArgument('bundle'));
        $license = file_get_contents($kernel->locateResource('@JMSCommandBundle/Resources/skeleton/License/'.$input->getOption('license')));

        foreach (Finder::create()->name('*.php')->in($bundle->getPath()) as $file) {
            $tokens = token_get_all(file_get_contents($file->getPathname()));

            $content = '';
            $afterNamespace = false;
            for ($i=0, $c=count($tokens); $i<$c; $i++) {
                if (!is_array($tokens[$i])) {
                    $content .= $tokens[$i];
                    continue;
                }

                if (!$afterNamespace && (T_COMMENT === $tokens[$i][0] || T_WHITESPACE === $tokens[$i][0])) {
                    continue;
                }

                if (T_NAMESPACE === $tokens[$i][0]) {
                    $content .= "\n".$license."\n\n";
                    $afterNamespace = true;
                }

                $content .= $tokens[$i][1];
            }

            if ($afterNamespace === false) {
                continue;
            }

            file_put_contents($file->getPathname(), $content);
        }
    }
}