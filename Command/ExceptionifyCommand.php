<?php

/*
 * Copyright 2011 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JMS\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use JMS\CommandBundle\Generator\ExceptionGenerator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class ExceptionifyCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('exceptionify');
        $this->addArgument('bundle', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bundle = $this->getContainer()->get('kernel')->getBundle($input->getArgument('bundle'));
        $class = new \ReflectionClass($bundle);

        $generator = new ExceptionGenerator();
        $generator->generate($bundle->getPath(), $class->getNamespaceName());
    }
}