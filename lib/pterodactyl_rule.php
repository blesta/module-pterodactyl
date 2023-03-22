<?php
use Blesta\Core\Util\Validate\Server;
/**
 * Pterodactyl Rule helper
 *
 * @package blesta
 * @subpackage blesta.components.modules.Pterodactyl.lib
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class PterodactylRule
{
    /**
     * Parses an egg variable from Pterodactyl and returns a Blesta input validation rule array
     *
     * @param string $eggVariable The egg variabe from Pteerodactyl to create rules for and from
     * @return array A list of Blesta rules parsed from the Pterodactyl rule string
     */
    public function parseEggVariable($eggVariable)
    {
        // Get the name of the field being validated
        $fieldName = $eggVariable->attributes->name;

        // Parse rule string for regexes and remove them to simplify parsing
        $ruleString = $eggVariable->attributes->rules;

        // Match the regex strings and store them in an array
        $regexRuleStrings = [];
        preg_match_all("/regex\\:(\\/.*?\\/)/i", $ruleString, $regexRuleStrings);

        // Remove the regex stings and replace them with {{{regex}}}
        $regexFilteredRuleString = str_replace($regexRuleStrings[1] ?? [], '{{{regex}}}', $ruleString);

        // Get a the list of OR separated validation rules
        $ruleStrings = explode('|', $regexFilteredRuleString);

        // Parse rules from the string
        $rules = [];
        foreach ($ruleStrings as $ruleString) {
            $ruleParts = explode(':', $ruleString);
            $ruleName = str_replace('_', '', lcfirst(ucwords($ruleParts[0], '_')));

            $ruleParameters = [];
            if (isset($ruleParts[1])) {
                $ruleParameters = explode(',', $ruleParts[1]);
            }

            // Re-add filtered regexes
            if (!empty($regexRuleStrings[1])) {
                foreach ($ruleParameters as &$ruleParameter) {
                    $ruleParameter = str_replace('{{{regex}}}', array_shift($regexRuleStrings[1]), $ruleParameter);
                }
            }

            // Generate validation rule
            if (method_exists($this, $ruleName)) {
                $rules[$ruleName] = call_user_func_array(
                    [$this, $ruleName],
                    [$fieldName, $ruleParameters]
                );
            }
        }

        // Make all rules conditional on field existence
        if (strpos($eggVariable->attributes->rules, 'required') === false) {
            foreach ($rules as &$rule) {
                $rule['if_set'] = true;
            }
        }

        return $rules;
    }

    /**
     * Gets a rule to require the given field
     *
     * @param string $fieldName The name of the field to validate
     * @return array An array representing the validation rule
     */
    private function required($fieldName)
    {
        return [
            'rule' => 'isEmpty',
            'negate' => true,
            'message' => Language::_('PterodactylRule.!error.required', true, $fieldName)
        ];
    }

    /**
     * Gets a rule to validate the given field against a regex
     *
     * @param string $fieldName The name of the field to validate
     * @param array $params A list parameters for the validation rule
     * @return array An array representing the validation rule
     */
    private function regex($fieldName, array $params)
    {
        return [
            'rule' => ['matches', $params[0]],
            'message' => Language::_('PterodactylRule.!error.regex', true, $fieldName, $params[0])
        ];
    }

    /**
     * Gets a rule to validate the given field is numeric
     *
     * @param string $fieldName The name of the field to validate
     * @return array An array representing the validation rule
     */
    private function numeric($fieldName)
    {
        return [
            'rule' => 'is_numeric',
            'message' => Language::_('PterodactylRule.!error.numeric', true, $fieldName)
        ];
    }

    /**
     * Gets a rule to validate the given field is an integer
     *
     * @param string $fieldName The name of the field to validate
     * @return array An array representing the validation rule
     */
    private function integer($fieldName)
    {
        return [
            'rule' => function ($value) {
                return is_numeric($value) && intval($value) == $value;
            },
            'message' => Language::_('PterodactylRule.!error.integer', true, $fieldName)
        ];
    }

    /**
     * Gets a rule to validate the given field is a string
     *
     * @param string $fieldName The name of the field to validate
     * @return array An array representing the validation rule
     */
    private function string($fieldName)
    {
        return [
            'rule' => 'is_string',
            'message' => Language::_('PterodactylRule.!error.string', true, $fieldName)
        ];
    }

    /**
     * Gets a rule to validate the given field is alpha_numeric
     *
     * @param string $fieldName The name of the field to validate
     * @return array An array representing the validation rule
     */
    private function alphaNum($fieldName)
    {
        return [
            'rule' => 'ctype_alnum',
            'message' => Language::_('PterodactylRule.!error.alphaNum', true, $fieldName)
        ];
    }

    /**
     * Gets a rule to validate the given field is only alpha_numeric characters or dashes and underscores
     *
     * @param string $fieldName The name of the field to validate
     * @return array An array representing the validation rule
     */
    private function alphaDash($fieldName)
    {
        return [
            'rule' => function($value) {
                return ctype_alnum(preg_replace('/-|_/', '', $value));
            },
            'message' => Language::_('PterodactylRule.!error.alphaDash', true, $fieldName)
        ];
    }

    /**
     * Gets a rule to validate the given field is a valid URL
     *
     * @param string $fieldName The name of the field to validate
     * @return array An array representing the validation rule
     */
    private function url($fieldName)
    {
        return [
            'rule' => function($value) {
                $validator = new Server();
                return $validator->isUrl($value);
            },
            'message' => Language::_('PterodactylRule.!error.url', true, $fieldName)
        ];
    }

    /**
     * Gets a rule to validate the given field has a value with a given minimum
     *
     * @param string $fieldName The name of the field to validate
     * @param array $params A list parameters for the validation rule
     * @return array An array representing the validation rule
     */
    private function min($fieldName, array $params)
    {
        return [
            'rule' => function ($value) use ($params) {
                switch (gettype($value)) {
                    case 'string':
                        return strlen($value) >= $params[0];
                    case 'integer':
                        // Same as double
                    case 'double':
                        return $value >= $params[0];
                    case 'array':
                        return count($value) >= $params[0];
                }
            },
            'message' => Language::_('PterodactylRule.!error.min', true, $fieldName, $params[0])
        ];
    }

    /**
     * Gets a rule to validate the given field has a value with a given maximum
     *
     * @param string $fieldName The name of the field to validate
     * @param array $params A list parameters for the validation rule
     * @return array An array representing the validation rule
     */
    private function max($fieldName, array $params)
    {
        return [
            'rule' => function ($value) use ($params) {
                switch (gettype($value)) {
                    case 'string':
                        return strlen($value) <= $params[0];
                    case 'integer':
                        // Same as double
                    case 'double':
                        return $value <= $params[0];
                    case 'array':
                        return count($value) <= $params[0];
                }
            },
            'message' => Language::_('PterodactylRule.!error.max', true, $fieldName, $params[0])
        ];
    }

    /**
     * Gets a rule to validate the given field has a value within a given range
     *
     * @param string $fieldName The name of the field to validate
     * @param array $params A list parameters for the validation rule
     * @return array An array representing the validation rule
     */
    private function between($fieldName, array $params)
    {
        return [
            'rule' => function ($value) use ($params) {
                switch (gettype($value)) {
                    case 'string':
                        return strlen($value) >= $params[0] && strlen($value) <= $params[1];
                    case 'integer':
                        // Same as double
                    case 'double':
                        return $value >= $params[0] && $value <= $params[1];
                    case 'array':
                        return count($value) >= $params[0] && count($value) <= $params[1];
                }
            },
            'message' => Language::_('PterodactylRule.!error.between', true, $fieldName, $params[0], $params[1])
        ];
    }

    /**
     * Gets a rule to validate the given field has a numeric value within a given range
     *
     * @param string $fieldName The name of the field to validate
     * @param array $params A list parameters for the validation rule
     * @return array An array representing the validation rule
     */
    private function digitsBetween($fieldName, array $params)
    {
        return [
            'rule' => function ($value) use ($params) {
                return is_numeric($value) && strlen($value) >= $params[0] && strlen($value) <= $params[1];
            },
            'message' => Language::_('PterodactylRule.!error.digitsBetween', true, $fieldName, $params[0], $params[1])
        ];
    }
}
