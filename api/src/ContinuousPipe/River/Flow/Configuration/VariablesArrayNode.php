<?php

namespace ContinuousPipe\River\Flow\Configuration;

use Symfony\Component\Config\Definition\PrototypedArrayNode;

class VariablesArrayNode extends PrototypedArrayNode
{
    /**
     * {@inheritdoc}
     */
    protected function mergeValues($leftSide, $rightSide)
    {
        if (false === $rightSide) {
            // if this is still false after the last config has been merged the
            // finalization pass will take care of removing this key entirely
            return false;
        }

        if (false === $leftSide || !$this->performDeepMerging) {
            return $rightSide;
        }

        foreach ($rightSide as $k => $v) {
            if (false === ($index = $this->findVariable($leftSide, $v))) {
                $leftSide[] = $v;
            } else {
                $this->prototype->setName($k);
                $leftSide[$index] = $this->prototype->merge($leftSide[$index], $v);
            }
        }

        return $leftSide;
    }

    /**
     * @param array $leftSide
     * @param array $variable
     *
     * @return int|bool
     */
    private function findVariable(array $leftSide, array $variable)
    {
        foreach ($leftSide as $index => $foundVariable) {
            if ($this->isSameVariable($foundVariable, $variable)) {
                return $index;
            }
        }

        return false;
    }

    private function isSameVariable(array $foundVariable, array $variable) : bool
    {
        if ($foundVariable['name'] != $variable['name']) {
            return false;
        }

        if (!isset($variable['condition'])) {
            $variable['condition'] = true;
        }
        if (!isset($foundVariable['condition'])) {
            $foundVariable['condition'] = true;
        }

        return $variable['condition'] == $foundVariable['condition'];
    }
}
