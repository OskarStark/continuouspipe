<?php

namespace ContinuousPipe\River\Flow\ConfigurationFinalizer;

use ContinuousPipe\River\CodeReference;
use ContinuousPipe\River\Flow\ConfigurationFinalizer;
use ContinuousPipe\River\Flow\EncryptedVariable\EncryptedVariableVault;
use ContinuousPipe\River\Flow\EncryptedVariable\EncryptionException;
use ContinuousPipe\River\Flow\Projections\FlatFlow;
use ContinuousPipe\River\Flow\Variable\FlowVariableResolver;
use ContinuousPipe\River\Tide\Configuration\ArrayObject;
use ContinuousPipe\River\TideConfigurationException;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\SyntaxError;

class ReplaceEnvironmentVariableValues implements ConfigurationFinalizer
{
    /**
     * @var EncryptedVariableVault
     */
    private $encryptedVariableVault;
    /**
     * @var FlowVariableResolver
     */
    private $flowVariableResolver;

    public function __construct(FlowVariableResolver $flowVariableResolver, EncryptedVariableVault $encryptedVariableVault)
    {
        $this->encryptedVariableVault = $encryptedVariableVault;
        $this->flowVariableResolver = $flowVariableResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function finalize(FlatFlow $flow, CodeReference $codeReference, array $configuration)
    {
        $variableContext = $this->flowVariableResolver->createContext($flow->getUuid(), $codeReference);

        // Replace the pipeline variables first
        foreach ($configuration['pipelines'] as &$pipeline) {
            $variables = $this->resolveVariables($flow, $pipeline, $variableContext);
            $pipeline = self::replaceValues($pipeline, $variables);
        }

        // Replace the tasks variables
        $variables = $this->resolveVariables($flow, $configuration, $variableContext);
        $configuration = self::replaceValues($configuration, $variables);

        return $configuration;
    }

    /**
     * @param FlatFlow    $flow
     * @param array       $configuration
     * @param ArrayObject $context
     *
     * @throws TideConfigurationException
     *
     * @return array
     */
    private function resolveVariables(FlatFlow $flow, array $configuration, ArrayObject $context)
    {
        if (!isset($configuration['variables'])) {
            return [];
        }

        $variables = [];
        foreach ($configuration['variables'] as $item) {
            if (array_key_exists('condition', $item) && !$this->isConditionValid($item['condition'], $context)) {
                continue;
            }

            if (array_key_exists('expression', $item)) {
                $item['value'] = $this->flowVariableResolver->resolveExpression($item['expression'], $context);
            }

            if (array_key_exists('encrypted_value', $item)) {
                try {
                    $value = $this->encryptedVariableVault->decrypt($flow->getUuid(), $item['encrypted_value']);
                } catch (EncryptionException $e) {
                    throw new TideConfigurationException(sprintf(
                        'Unable to decrypt the value of the variable "%s": %s',
                        $item['name'],
                        $e->getMessage()
                    ));
                }
            } elseif (array_key_exists('value', $item)) {
                $value = $item['value'];
            } else {
                throw new TideConfigurationException(sprintf(
                    'Unable to read the value of the variable "%s"',
                    $item['name']
                ));
            }

            $variables[$item['name']] = $value;
        }

        return $variables;
    }

    /**
     * @param array $array
     * @param array $mapping
     *
     * @return array
     */
    public static function replaceValues(array $array, array $mapping)
    {
        array_walk_recursive($array, function (&$value) use ($mapping) {
            if (is_string($value)) {
                $value = self::replaceVariables($value, $mapping);
            }
        });

        return $array;
    }

    /**
     * @param string $value
     * @param array  $mapping
     *
     * @return string
     */
    public static function replaceVariables(string $value, array $mapping)
    {
        $variableKeys = array_map(function ($key) {
            return sprintf('${%s}', $key);
        }, array_keys($mapping));

        return str_replace($variableKeys, array_values($mapping), $value);
    }

    /**
     * @param string      $condition
     * @param ArrayObject $context
     *
     * @return bool
     *
     * @throws TideConfigurationException
     */
    private function isConditionValid($condition, ArrayObject $context)
    {
        return (bool) $this->flowVariableResolver->resolveExpression($condition, $context);
    }
}
