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

namespace JMS\CommandBundle\Generator;

use JMS\CommandBundle\Php\Lexer;

class ExceptionGenerator
{
    private static $splExceptions = array(
        '\BadFunctionCallException', '\BadMethodCallException', '\DomainException',
        '\InvalidArgumentException', '\LengthException', '\LogicException',
        '\OutOfBoundsException', '\OutOfRangeException', '\OverflowException',
        '\RangeException', '\RuntimeException', '\UnderflowException',
        '\UnexpectedValueException',
    );

    private $lexer;

    public function __construct()
    {
        $this->lexer = new Lexer();
    }

    public function generate($rootDir, $rootNamespace)
    {
        if (!is_dir($rootDir)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a directory.', $rootDir));
        }
        $rootNamespace = rtrim($rootNamespace, '\\');

        $component = new \SplFileInfo($rootDir);

        // scan files
        $exceptions = array();
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($component->getPathName())) as $file) {
            if ('.php' !== substr($file->getFileName(), -4)) {
                continue;
            }

            $foundExceptions = array();

            // locate exceptions
            $this->lexer->setInput(file_get_contents($file->getRealPath()));
            $code = '';
            $this->lexer->moveNext();

            $useStatements = array();
            $fqcn = null;
            $extends = $implements = $class = null;
            while (null !== $this->lexer->lookahead) {
                $this->lexer->moveNext();

                if ($this->lexer->token['type'] === T_NEW
                    || $this->lexer->token['type'] === T_USE
                    || $this->lexer->token['type'] === T_EXTENDS
                    || $this->lexer->token['type'] === T_CLASS
                    || $this->lexer->token['type'] === T_IMPLEMENTS) {
                    $start = $this->lexer->token['type'];
                    $fqcn = '';
                } else if ($this->lexer->token['type'] === T_STRING || $this->lexer->token['type'] === T_NS_SEPARATOR) {
                    if (null !== $fqcn) {
                        $fqcn .= $this->lexer->token['value'];
                        continue;
                    }
                } else {
                    if (null !== $fqcn && '' !== $fqcn) {
                        if ($start === T_NEW) {
                            if (in_array($fqcn, self::$splExceptions)) {
                                $code .= substr($fqcn, 1);
                                $foundExceptions[] = $fqcn;
                            } else {
                                $code .= $fqcn;
                            }
                        } else if ($start === T_USE) {
                            $code .= $fqcn;
                            $useStatements[] = $fqcn;
                        } else if ($start === T_EXTENDS) {
                            $extends = $fqcn;
                            $code .= $fqcn;
                        } else if ($start === T_CLASS) {
                            $class = $fqcn;
                            $code .= $fqcn;
                        } else if ($start === T_IMPLEMENTS) {
                            $class = $fqcn;
                            $code .= $fqcn;
                        }
                    }

                    if ($this->lexer->token['type'] !== T_WHITESPACE || $fqcn !== '') {
                        $fqcn = null;
                    }
                }

                $code .= $this->lexer->token['value'];
            }

            $this->lexer->setInput($code);
            $this->lexer->moveNext();
            $foundExceptions = array_unique($foundExceptions);
            $code = '';

            if (!$foundExceptions) {
                continue;
            }

            $added = false;
            $inNamespace = false;
            while (null !== $this->lexer->lookahead) {
                $this->lexer->moveNext();

                if ($useStatements && $this->lexer->token['type'] === T_USE) {
                    if (!$added) {
                        $added = true;
                        foreach ($foundExceptions as $ex) {
                            $import = $rootNamespace.'\Exception'.$ex;
                            if (in_array($import, $useStatements)) {
                                continue;
                            }

                            $code .= 'use '.$import.";\n";
                        }
                    }
                } else if (!$useStatements && $this->lexer->token['type'] === T_NAMESPACE) {
                    $inNamespace = true;
                } else if ($inNamespace && $this->lexer->token['type'] === T_LITERAL && $this->lexer->token['value'] === ';') {
                    $code .= ";\n";
                    $inNamespace = false;

                    foreach ($foundExceptions as $ex) {
                        $import = $rootNamespace.'\Exception'.$ex;
                        $code .= "\nuse ".$import.";";
                    }

                    continue;
                }

                $code .= $this->lexer->token['value'];
            }

            file_put_contents($file->getRealPath(), $code);
            $exceptions[] = $foundExceptions;
        }

        if (!$exceptions) {
            return;
        }

        $exceptions = call_user_func_array('array_merge', $exceptions);
        $exceptions = array_unique($exceptions);

        if (!is_dir($exDir = $component->getPathName().'/Exception')) {
            mkdir($exDir);
        }

        if (!file_exists($exDir.'/Exception.php')) {
            ob_start();
            include __DIR__.'/../Resources/skeleton/Exception/Exception.php';
            $exTemplate = ob_get_contents();
            ob_end_clean();

            $exTemplate = str_replace('[?php', '<?php', $exTemplate);
            file_put_contents($exDir.'/Exception.php', $exTemplate);
        }

        foreach ($exceptions as $ex) {
            if (file_exists($path = $exDir.'/'.substr($ex, 1).'.php')) {
                continue;
            }

            ob_start();
            include __DIR__.'/../Resources/skeleton/Exception/SPLException.php';
            $template = ob_get_contents();
            ob_end_clean();

            $template = str_replace('[?php', '<?php', $template);
            file_put_contents($path, $template);
        }
    }
}