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

namespace JMS\CommandBundle\Php;

define('T_LITERAL', -1);

class Lexer extends \Doctrine\Common\Lexer
{
    private $refToken;

    public function __construct()
    {
        $this->refToken = new \ReflectionProperty('Doctrine\Common\Lexer', 'tokens');
        $this->refToken->setAccessible(true);
    }

    public function skipWhitespace()
    {
        while ($this->lookahead['type'] === T_WHITESPACE && $this->moveNext());
    }

    private function setTokens(array $tokens)
    {
        $this->refToken->setValue($this, $tokens);
    }

    protected function scan($input)
    {
        $tokens = array();
        foreach (token_get_all($input) as $token) {
            if (is_string($token)) {
                $tokens[] = array(
                    'value'    => $token,
                    'type'     => T_LITERAL,
                    'position' => count($tokens),
                );
            } else {
                $tokens[] = array(
                    'value'    => $token[1],
                    'type'     => $token[0],
                    'position' => count($tokens),
                );
            }
        }

        $this->setTokens($tokens);
    }

    protected function getCatchablePatterns()
    {
        return array();
    }

    protected function getNonCatchablePatterns()
    {
        return array();
    }

    protected function getType(&$value)
    {
        throw new \RuntimeException('not supported.');
    }

    public function getLiteral($token)
    {
        return token_name($token);
    }
}