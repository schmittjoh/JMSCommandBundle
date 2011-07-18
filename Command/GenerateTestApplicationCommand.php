<?php

namespace JMS\CommandBundle\Command;

use Symfony\Component\Console\Input\InputOption;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class GenerateTestApplicationCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('generate:test-application')
            ->setDescription('Generates a sample application inside a bundle that you can use for functional tests.')
            ->addArgument('bundle', InputArgument::REQUIRED, 'The full bundle name, e.g. JMSCommandBundle')
            ->addOption('with-database', null, InputOption::VALUE_NONE, 'Whether to generate database support')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bundle = $this->getContainer()->get('kernel')->getBundle($input->getArgument('bundle'));

        $files = array(
            '/AppKernel.php' => array(
                'namespace' => $bundle->getNamespace(),
                'name' => $bundle->getName(),
                'withDatabase' => $input->getOption('with-database'),
            ),
            '/BaseTestCase.php' => array(
                'namespace' => $bundle->getNamespace(),
                'withDatabase' => $input->getOption('with-database'),
            ),
            '/config/default.yml' => array(
                'withDatabase' => $input->getOption('with-database'),
            ),
            '/config/framework.yml' => array(
            ),
            '/config/routing.yml' => array(

            ),
            '/config/twig.yml' => array(
            )
        );

        if ($input->getOption('with-database')) {
            $files['/config/doctrine.yml'] = array();
        }

        $testDir = $bundle->getPath().'/Tests/Functional';
        foreach ($files as $name => $params) {
            $file = $testDir.$name;
            if (!file_exists($dir = dirname($file)) && false === @mkdir($dir, 0777, true)) {
                throw new \RuntimeException(sprintf('Could not create directory "%s".', $dir));
            }

            if (file_exists($file)) {
                continue;
            }

            file_put_contents($file, $this->render('@JMSCommandBundle/Resources/skeleton/Application'.$name, $params));
            $output->writeln(sprintf('[File+] <comment>%s</comment>', $file));
        }

        $output->writeln('Test Application has been generated successfully.');
    }

    private function render($jms_name, $jms_params = array())
    {
        $jms_path = $this->getContainer()->get('kernel')->locateResource($jms_name);

        extract($jms_params, EXTR_SKIP);
        ob_start();
        require $jms_path;
        $content = str_replace('[?php', '<?php', ob_get_contents());
        ob_end_clean();

        return $content;
    }
}